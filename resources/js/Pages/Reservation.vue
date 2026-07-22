<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()

const currentTab = ref(page.props.currentTab || 'pending')
const showModal = ref(false)
const showCalendarModal = ref(false)
const selectedBooking = ref(null)
const processing = ref(null)
const selectedMonth = ref(new Date().getMonth())
const selectedYear = ref(new Date().getFullYear())
const selectedRoomTypeId = ref(null)
let pollInterval = null

// Month/year options
const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const years = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => currentYear - 2 + i)
})

function approve(booking) {
    if (!confirm('Approve this booking?')) return
    processing.value = booking.id
    router.post(route('reservation.approve', { booking: booking.id }), {}, {
        preserveState: true,
        onFinish: () => { processing.value = null },
    })
}

function decline(booking) {
    if (!confirm('Decline this booking?')) return
    processing.value = booking.id
    router.post(route('reservation.decline', { booking: booking.id }), {}, {
        preserveState: true,
        onFinish: () => { processing.value = null },
    })
}

function checkin(booking) {
    if (!confirm('Check in this guest?')) return
    processing.value = booking.id
    router.post(route('reservation.checkin', { booking: booking.id }), {}, {
        preserveState: true,
        onFinish: () => { processing.value = null },
    })
}

function checkout(booking) {
    if (!confirm('Check out this guest? This will archive the booking.')) return
    processing.value = booking.id
    router.post(route('reservation.checkout', { booking: booking.id }), {}, {
        preserveState: true,
        onFinish: () => { processing.value = null },
    })
}

const tabs = {
    pending: 'Pending Request',
    approved: 'Confirmed',
    checked_in: 'Checked In',
    checked_out: 'Archived History',
}

const bookings = computed(() => {
    return (page.props.allBookings || []).filter(b => b.status === currentTab.value)
})

const allBookings = computed(() => {
    return page.props.allBookings || []
})

// Get unique room types from bookings
const roomTypes = computed(() => {
    const typeMap = {}
    allBookings.value.forEach(booking => {
        if (booking.room?.room_type?.id && !typeMap[booking.room.room_type.id]) {
            typeMap[booking.room.room_type.id] = {
                id: booking.room.room_type.id,
                name: booking.room.room_type?.name || 'Standard Unit'
            }
        }
    })
    return Object.values(typeMap)
})

// Get days in selected month
const daysInMonth = computed(() => {
    const count = new Date(selectedYear.value, selectedMonth.value + 1, 0).getDate()
    return Array.from({ length: count }, (_, i) => i + 1)
})

// Get rooms for selected room type
const filteredRooms = computed(() => {
    if (!selectedRoomTypeId.value) return []
    return allBookings.value
        .filter(b => b.room?.room_type?.id === selectedRoomTypeId.value)
        .map(b => ({
            id: b.room?.id,
            number: b.room?.room_number,
            type: b.room?.room_type?.name
        }))
        .filter((r, i, arr) => arr.findIndex(item => item.id === r.id) === i)
})

// Get booking for a specific room and day
function getBookingForDay(roomId, day) {
    const targetMonthStr = String(selectedMonth.value + 1).padStart(2, '0')
    const targetDayStr = String(day).padStart(2, '0')
    const targetDateStr = `${selectedYear.value}-${targetMonthStr}-${targetDayStr}`
    
    return allBookings.value.find(booking => {
        if (booking.room?.id !== roomId) return false
        if (!booking.start_at) return false
        
        // Parse dates and compare as strings (YYYY-MM-DD format)
        const startDate = new Date(booking.start_at)
        const endDate = new Date(booking.end_at)
        
        const startYear = startDate.getFullYear()
        const startMonth = String(startDate.getMonth() + 1).padStart(2, '0')
        const startDay = String(startDate.getDate()).padStart(2, '0')
        const startDateStr = `${startYear}-${startMonth}-${startDay}`
        
        const endYear = endDate.getFullYear()
        const endMonth = String(endDate.getMonth() + 1).padStart(2, '0')
        const endDay = String(endDate.getDate()).padStart(2, '0')
        const endDateStr = `${endYear}-${endMonth}-${endDay}`
        
        return targetDateStr >= startDateStr && targetDateStr <= endDateStr
    })
}

// Check if a day is booked for a room
function isBooked(roomId, day) {
    return getBookingForDay(roomId, day) !== undefined
}

onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({ preserveState: true, preserveScroll: true, only: ['allBookings'] })
    }, 15000)
})

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval)
})

function changeTab(tab) {
    currentTab.value = tab
}

function viewDetails(booking) {
    selectedBooking.value = booking
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    selectedBooking.value = null
}

function openCalendar() {
    showCalendarModal.value = true
}

function closeCalendar() {
    showCalendarModal.value = false
}

function formatAmount(amount) {
    if (!amount) return '0.00'
    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2 })
}

function statusClass(status) {
    const map = {
        pending: 'badge-warning',
        approved: 'badge-info',
        checked_in: 'badge-success',
        checked_out: 'badge-neutral',
        cancelled: 'badge-danger',
        rejected: 'badge-danger',
    }
    return map[status] || 'badge-neutral'
}

function dayStatusClass(status) {
    const map = {
        approved: 'border-blue-200 bg-blue-50 text-blue-700',
        checked_in: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    }
    return map[status] || 'border-emerald-100 bg-emerald-50/60 text-emerald-800'
}

function toHumanReadableDate(dateString) {
    if (!dateString) return '—'
    const parts = dateString.split('T')[0].split('-')
    if (parts.length !== 3) return dateString
    const year = parts[0]
    const monthIndex = parseInt(parts[1], 10) - 1
    const day = parseInt(parts[2], 10)
    return `${months[monthIndex] || ''} ${String(day).padStart(2, '0')}, ${year}`
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-(screen-2xl) mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 page-header">
                <div>
                    <h1 class="text-heading-1">Reservations Desk</h1>
                    <p class="text-body text-gray-500 mt-1">Monitor guest bookings, assign available rooms, and manage current occupancy stats.</p>
                </div>

                <!-- Availability Matrix Button -->
                <button @click="openCalendar"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900 active:scale-98 cursor-pointer w-full sm:w-auto">
                    <i class="ti ti-calendar-stats text-base text-teal-600"></i>
                    <span>Availability Matrix</span>
                </button>
            </div>

            <!-- Tabs -->
            <div class="tab-group">
                <div class="flex gap-1 overflow-x-auto scrollbar-thin snap-x">
                    <button v-for="(label, key) in tabs" :key="key"
                            @click="changeTab(key)"
                            class="tab-button min-w-[110px] text-center"
                            :class="currentTab === key ? 'tab-button-active' : 'tab-button-inactive'">
                        {{ label }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <div class="overflow-x-auto w-full">
                    <table class="min-w-[1000px] w-full data-table">
                        <thead>
                            <tr>
                                <th class="w-2/5">Guest Name</th>
                                <th class="w-1/5">Assigned Room</th>
                                <th class="w-1/5">Check-In</th>
                                <th class="w-1/5">Check-Out</th>
                                <th class="w-1/6">Status</th>
                                <th class="w-1/6">Total Price</th>
                                <th class="w-1/6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="booking in bookings" :key="booking.id"
                                class="transition-colors hover:bg-gray-50/50"
                                :class="booking.is_conflicted ? 'bg-red-50/40 hover:bg-red-50/80' : ''">
                                <td>
                                    <div class="font-semibold text-gray-900">
                                        {{ booking.user?.full_name || 'Unknown Guest' }}
                                    </div>
                                    
                                    <div v-if="booking.is_conflicted"
                                         class="inline-flex items-center gap-1.5 text-[10px] font-medium text-red-700 bg-red-100/80 px-2 py-0.5 rounded-md mt-1.5 uppercase tracking-wide">
                                        <i class="ti ti-alert-triangle text-xs"></i>
                                        Schedule Conflict
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium">{{ booking.room?.room_type?.name || '—' }}</span>
                                        <span class="inline-flex items-center self-start mt-1 text-[11px] font-bold px-2 py-0.5 rounded-md"
                                              :class="booking.is_conflicted ? 'text-red-700 bg-red-100/60' : 'text-teal-700 bg-teal-50'">
                                            Room #{{ booking.room?.room_number || 'N/A' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-caption font-medium">{{ booking.start_at_formatted || '—' }}</td>
                                <td class="text-caption font-medium">{{ booking.end_at_formatted || '—' }}</td>
                                <td>
                                    <span class="badge" :class="booking.is_conflicted ? 'badge-danger' : statusClass(booking.status)">
                                        {{ booking.is_conflicted ? 'Conflict' : (tabs[booking.status] || booking.status.replace('_', ' ')) }}
                                    </span>
                                </td>
                                <td class="font-bold text-gray-900">₱{{ formatAmount(booking.total_amount) }}</td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="viewDetails(booking)"
                                                class="btn btn-secondary">
                                            <i class="ti ti-eye text-xs"></i>
                                            Details
                                        </button>
                                        
                                        <button v-if="booking.status === 'pending'"
                                                @click="approve(booking)"
                                                :disabled="processing === booking.id || booking.is_conflicted"
                                                class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                            <i class="ti ti-circle-check text-xs"></i>
                                            Approve
                                        </button>
                                        <button v-if="booking.status === 'pending'"
                                                @click="decline(booking)"
                                                :disabled="processing === booking.id"
                                                class="btn btn-secondary">
                                            <i class="ti ti-x text-xs"></i>
                                            Decline
                                        </button>

                                        <button v-if="booking.status === 'approved'"
                                                @click="checkin(booking)"
                                                :disabled="processing === booking.id"
                                                class="btn btn-primary">
                                            <i class="ti ti-login text-xs"></i>
                                            Check In
                                        </button>

                                        <button v-if="booking.status === 'checked_in'"
                                                @click="checkout(booking)"
                                                :disabled="processing === booking.id"
                                                class="btn btn-secondary">
                                            <i class="ti ti-logout text-xs"></i>
                                            Check Out
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="bookings.length === 0">
                                <td colspan="7" class="p-12">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="ti ti-calendar-off text-3xl text-gray-300"></i>
                                        </div>
                                        <p class="text-body font-medium text-gray-500">No bookings found</p>
                                        <p class="text-caption mt-1">There are no records listed inside this status category tab layout.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Availability Matrix Calendar Modal -->
            <Teleport to="body">
                <div v-if="showCalendarModal" class="modal-backdrop" @click.self="closeCalendar">
                    <div class="modal-container" @click.self="closeCalendar">
                        <div class="modal-panel w-full max-w-7xl max-h-[90vh] overflow-y-auto">
                            <div class="absolute top-4 right-4 z-20">
                                <button @click="closeCalendar"
                                        class="btn-icon">
                                    <i class="ti ti-x text-lg text-gray-500"></i>
                                </button>
                            </div>

                            <div class="mt-2">
                                <div class="mb-6 flex items-center justify-between page-header">
                                    <div>
                                        <h2 class="text-heading-2">Room Type Availability Matrix</h2>
                                        <p class="text-body text-gray-500 mt-1">Real-time occupancy status for confirmed reservations.</p>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-500/10">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Live Data Sync</span>
                                    </span>
                                </div>

                                <div class="mb-6 flex flex-wrap items-center gap-3">
                                    <div class="relative min-w-[160px]">
                                        <select v-model.number="selectedMonth"
                                                class="w-full appearance-none rounded-xl border border-gray-200 bg-gray-50/50 px-3.5 py-2.5 text-sm font-medium text-gray-700 outline-none focus:ring-2 focus:ring-teal-500 transition cursor-pointer">
                                            <option v-for="(label, index) in months" :key="index" :value="index"
                                                    :selected="selectedMonth === index" class="font-medium text-gray-900">
                                                {{ label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <input type="number" v-model.number="selectedYear"
                                               class="w-32 rounded-xl border border-gray-200 bg-gray-50/50 px-3.5 py-2.5 text-sm font-medium text-gray-700 outline-none focus:ring-2 focus:ring-teal-500 transition">
                                    </div>
                                </div>

                                <div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-5">
                                    <button v-for="type in roomTypes" :key="type.id"
                                            @click="selectedRoomTypeId = selectedRoomTypeId === type.id ? null : type.id"
                                            class="rounded-xl border px-4 py-2 text-xs font-semibold uppercase tracking-wider shadow-sm transition active:scale-[0.98] cursor-pointer"
                                            :class="selectedRoomTypeId === type.id
                                                ? 'border-teal-600 bg-teal-600 text-white'
                                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                                        {{ type.name }}
                                    </button>
                                </div>

                                <div v-if="!selectedRoomTypeId" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gray-50/50 p-10 text-center text-gray-400">
                                    <i class="ti ti-click text-2xl text-gray-300 mb-2"></i>
                                    <p class="text-body font-medium">Select a lodging variant above to populate the availability matrix grids.</p>
                                </div>

                                <div v-else>
                                    <div class="space-y-4">
                                        <div v-for="room in filteredRooms" :key="room.id"
                                             class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex items-center gap-2 mb-3">
                                                <span class="text-xs font-bold text-teal-800 bg-teal-50 px-2.5 py-1 rounded">
                                                    Room #{{ room.number }}
                                                </span>
                                                <span class="text-xs text-gray-500">{{ room.type }}</span>
                                            </div>
                                            
                                            <div class="grid grid-cols-7 gap-2 select-none text-sm">
                                                <div v-for="day in daysInMonth" :key="day"
                                                     class="relative group">
                                                    <div class="rounded-xl border p-3.5 text-center transition duration-150 flex flex-col items-center justify-center gap-1 min-h-[56px] cursor-pointer"
                                                            :class="isBooked(room.id, day)
                                                                ? 'border-blue-200 bg-blue-50 text-blue-700 font-medium'
                                                                : 'border-emerald-100 bg-emerald-50/60 text-emerald-800'"
                                                            @click="getBookingForDay(room.id, day) && viewDetails(getBookingForDay(room.id, day))">
                                                        <span class="text-xs font-medium">{{ day }}</span>
                                                        <span class="text-[10px] uppercase tracking-wider font-semibold block opacity-75">
                                                            {{ isBooked(room.id, day) ? 'Full' : 'Open' }}
                                                        </span>
                                                    </div>

                                                    <div v-if="getBookingForDay(room.id, day)" class="absolute bottom-full left-1/2 z-50 mb-2 w-56 -translate-x-1/2 scale-95 opacity-0 transition-all duration-200 pointer-events-none group-hover:scale-100 group-hover:opacity-100">
                                                        <div class="rounded-lg bg-gray-900 p-3 text-xs text-white shadow-xl ring-1 ring-black/10">
                                                            <div class="font-bold tracking-wide uppercase border-b border-gray-700 pb-1 mb-1.5 flex justify-between items-center">
                                                                <span>Allocation Info</span>
                                                                <span class="px-1.5 rounded font-bold text-[10px] bg-blue-500 text-white"
                                                                      :class="getBookingForDay(room.id, day).status === 'checked_in' ? 'bg-emerald-500' : 'bg-blue-500'">
                                                                    {{ getBookingForDay(room.id, day).status.replace('_', ' ') }}
                                                                </span>
                                                            </div>
                                                            <div class="font-medium text-gray-200">{{ getBookingForDay(room.id, day).user?.full_name || 'Unknown Guest' }}</div>
                                                            
                                                            <div class="text-[11px] text-gray-400 font-medium mt-1.5 space-y-0.5">
                                                                <div>
                                                                    <span class="text-gray-500 font-mono text-[10px]">IN:</span>
                                                                    <span>{{ toHumanReadableDate(getBookingForDay(room.id, day).start_at) }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="text-gray-500 font-mono text-[10px]">OUT:</span>
                                                                    <span>{{ toHumanReadableDate(getBookingForDay(room.id, day).end_at) }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="absolute top-full left-1/2 h-2 w-2 -translate-x-1/2 -translate-y-1 rotate-45 bg-gray-900"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-5 flex items-center justify-end gap-4 text-xs font-medium text-gray-500 px-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded bg-emerald-100 border border-emerald-200 inline-block"></span>
                                            <span>Vacant / Available</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded bg-blue-50 border border-blue-200 inline-block"></span>
                                            <span>Reserved / Occupied</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Details Modal -->
            <Teleport to="body">
                <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
                    <div class="modal-container" @click.self="closeModal">
                        <div class="modal-panel w-full max-w-4xl">
                            <div v-if="selectedBooking" class="contents">
                                <!-- Modal Header -->
                                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                                    <div>
                                        <h3 class="text-heading-2">
                                            Booking Record #{{ selectedBooking.id }}
                                        </h3>
                                        <p class="text-caption mt-1">Reservation Details</p>
                                    </div>
                                    <button @click="closeModal" class="btn-icon">
                                        <i class="ti ti-x text-lg text-gray-500"></i>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-6 text-sm text-gray-600">
                                    <!-- Stay Information -->
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                        <div class="flex items-center gap-2 mb-4">
                                            <i class="ti ti-bed text-lg text-teal-600"></i>
                                            <h4 class="text-heading-3">Stay Information</h4>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-label">Status:</span>
                                                <span class="badge" :class="statusClass(selectedBooking.status)">
                                                    {{ selectedBooking.status.replace('_', ' ') }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Room:</span>
                                                <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-0.5 text-xs font-bold text-teal-700">#{{ selectedBooking.room?.room_number || 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Room Type:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.room?.room_type?.name || selectedBooking.room_type || 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Floor:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.room?.floor || 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Capacity:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.room?.room_type?.capacity || 'N/A' }}</span>
                                            </div>
                                            <div class="border-t border-gray-200 pt-3 space-y-2">
                                                <div class="flex flex-col">
                                                    <span class="text-label">CHECK-IN</span>
                                                    <span class="font-mono text-gray-800 font-medium text-sm">{{ selectedBooking.start_at_formatted }}</span>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-label">CHECK-OUT</span>
                                                    <span class="font-mono text-gray-600 text-sm">{{ selectedBooking.end_at_formatted }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Guest Information -->
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                        <div class="flex items-center gap-2 mb-4">
                                            <i class="ti ti-user text-lg text-teal-600"></i>
                                            <h4 class="text-heading-3">Guest Information</h4>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex flex-col">
                                                <span class="text-label">Guest Name:</span>
                                                <span class="text-gray-900 font-bold text-base mt-0.5">{{ selectedBooking.user?.full_name || 'System Guest' }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-label">Email:</span>
                                                <span class="text-gray-700 font-medium select-all font-mono text-xs break-all mt-0.5">{{ selectedBooking.user?.email || 'N/A' }}</span>
                                            </div>
                                            <div class="flex flex-col" v-if="selectedBooking.user?.phone">
                                                <span class="text-label">Phone:</span>
                                                <span class="text-gray-700 font-medium font-mono text-xs mt-0.5">{{ selectedBooking.user?.phone }}</span>
                                            </div>
                                            <div class="flex flex-col" v-if="selectedBooking.user?.address">
                                                <span class="text-label">Address:</span>
                                                <span class="text-gray-700 font-medium text-xs mt-0.5">{{ selectedBooking.user?.address }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Guest Count -->
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                        <div class="flex items-center gap-2 mb-4">
                                            <i class="ti ti-users text-lg text-teal-600"></i>
                                            <h4 class="text-heading-3">Guest Composition</h4>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex justify-between">
                                                <span class="text-label">Adults:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.guests || 1 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Child:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.has_child ? 1 : 0 }}</span>
                                            </div>
                                            <div class="flex justify-between" v-if="selectedBooking.child_age_group">
                                                <span class="text-label">Child Age Group:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.child_age_group }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">PWD:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.has_pwd ? 1 : 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Senior:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.has_senior ? 1 : 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Extra Beds:</span>
                                                <span class="text-gray-900 font-medium">{{ selectedBooking.extra_beds || 0 }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment & Pricing -->
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                        <div class="flex items-center gap-2 mb-4">
                                            <i class="ti ti-credit-card text-lg text-teal-600"></i>
                                            <h4 class="text-heading-3">Payment & Pricing</h4>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex justify-between">
                                                <span class="text-label">Payment Method:</span>
                                                <span class="uppercase font-mono text-xs font-bold bg-white px-2 py-0.5 rounded border border-gray-200 text-gray-800">{{ selectedBooking.payment_method || 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between" v-if="selectedBooking.payment_choice">
                                                <span class="text-label">Payment Choice:</span>
                                                <span class="uppercase font-mono text-xs font-medium bg-white px-2 py-0.5 rounded border border-gray-200 text-gray-800">{{ selectedBooking.payment_choice }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Price at Booking:</span>
                                                <span class="text-gray-900 font-medium">₱{{ formatAmount(selectedBooking.price_at_booking) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-label">Total Amount:</span>
                                                <span class="text-emerald-600 font-bold">₱{{ formatAmount(selectedBooking.total_amount) }}</span>
                                            </div>
                                            <div class="flex justify-between" v-if="selectedBooking.amount_paid">
                                                <span class="text-label">Amount Paid:</span>
                                                <span class="text-blue-600 font-medium">₱{{ formatAmount(selectedBooking.amount_paid) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action History -->
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                        <div class="flex items-center gap-2 mb-4">
                                            <i class="ti ti-history text-lg text-teal-600"></i>
                                            <h4 class="text-heading-3">Action History</h4>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex flex-col">
                                                <span class="text-label">Approved By:</span>
                                                <span class="text-gray-900 font-medium mt-0.5">{{ selectedBooking.approved_by?.full_name || '—' }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-label">Declined By:</span>
                                                <span class="text-gray-900 font-medium mt-0.5">{{ selectedBooking.rejected_by?.full_name || '—' }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-label">Checked In By:</span>
                                                <span class="text-gray-900 font-medium mt-0.5">{{ selectedBooking.checked_in_by?.full_name || '—' }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-label">Checked Out By:</span>
                                                <span class="text-gray-900 font-medium mt-0.5">{{ selectedBooking.checked_out_by?.full_name || '—' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Timestamps -->
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                        <div class="flex items-center gap-2 mb-4">
                                            <i class="ti ti-clock text-lg text-teal-600"></i>
                                            <h4 class="text-heading-3">Timestamps</h4>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex flex-col">
                                                <span class="text-label">Created At:</span>
                                                <span class="text-gray-900 font-medium font-mono text-xs mt-0.5">{{ toHumanReadableDate(selectedBooking.created_at) }}</span>
                                            </div>
                                            <div class="flex flex-col" v-if="selectedBooking.checked_in_at">
                                                <span class="text-label">Checked In At:</span>
                                                <span class="text-gray-900 font-medium font-mono text-xs mt-0.5">{{ toHumanReadableDate(selectedBooking.checked_in_at) }}</span>
                                            </div>
                                            <div class="flex flex-col" v-if="selectedBooking.checked_out_at">
                                                <span class="text-label">Checked Out At:</span>
                                                <span class="text-gray-900 font-medium font-mono text-xs mt-0.5">{{ toHumanReadableDate(selectedBooking.checked_out_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="selectedBooking.message" class="mx-6 mb-6 p-4 bg-blue-50/30 rounded-xl border border-blue-100">
                                    <span class="text-blue-900 font-semibold text-label block mb-1">Guest Notes:</span>
                                    <span class="italic text-gray-600 font-medium">"{{ selectedBooking.message }}"</span>
                                </div>

                                <div v-if="selectedBooking.status === 'pending'" class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5 px-6 pb-6">
                                    <button @click="decline(selectedBooking)"
                                            :disabled="processing === selectedBooking.id"
                                            class="btn btn-secondary">
                                        {{ processing === selectedBooking.id ? 'Processing...' : 'Decline' }}
                                    </button>
                                    <button @click="approve(selectedBooking)"
                                            :disabled="processing === selectedBooking.id || selectedBooking.is_conflicted"
                                            class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="ti ti-circle-check text-xs"></i>
                                        {{ processing === selectedBooking.id ? 'Processing...' : 'Approve' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>