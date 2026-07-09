<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()
const props = page.props

const currentTab = ref(props.currentTab || 'pending')
const showModal = ref(false)
const showCalendarModal = ref(false)
const selectedBooking = ref(null)
const processing = ref(null)
const selectedMonth = ref(new Date().getMonth())
const selectedYear = ref(new Date().getFullYear())
const selectedRoomTypeId = ref(null)

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
    return (props.allBookings || []).filter(b => b.status === currentTab.value)
})

const allBookings = computed(() => {
    return props.allBookings || []
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
        pending: 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
        approved: 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/20',
        checked_in: 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
        checked_out: 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-500/10',
        cancelled: 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
        rejected: 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20',
    }
    return map[status] || 'bg-gray-100 text-gray-600'
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Reservations Desk</h1>
                    <p class="text-sm text-gray-500 mt-1">Monitor guest bookings, assign available rooms, and manage current occupancy stats.</p>
                </div>

                <!-- Availability Matrix Button -->
                <button @click="openCalendar"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900 active:scale-98 cursor-pointer w-full sm:w-auto">
                    <i class="ti ti-calendar-stats text-base text-teal-600"></i>
                    <span>Availability Matrix</span>
                </button>
            </div>

            <!-- Tabs -->
            <div class="rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm">
                <div class="flex gap-1 overflow-x-auto scrollbar-none snap-x">
                    <button v-for="(label, key) in tabs" :key="key"
                            @click="changeTab(key)"
                            class="whitespace-nowrap snap-clamp rounded-lg px-4 py-2 text-sm font-semibold transition-all duration-200 cursor-pointer min-w-[110px] text-center"
                            :class="currentTab === key ? 'bg-teal-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                        {{ label }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto w-full">
                    <table class="min-w-[1000px] w-full text-sm text-left border-collapse">
                        <thead class="border-b border-gray-200 bg-gray-50/80">
                            <tr>
                                <th scope="col" class="p-4 pl-5 font-semibold text-gray-600 w-1/4">Guest Name</th>
                                <th scope="col" class="p-4 font-semibold text-gray-600 w-1/5">Assigned Room</th>
                                <th scope="col" class="p-4 font-semibold text-gray-600 w-1/8">Check-In</th>
                                <th scope="col" class="p-4 font-semibold text-gray-600 w-1/8">Check-Out</th>
                                <th scope="col" class="p-4 font-semibold text-gray-600 w-1/10">Status</th>
                                <th scope="col" class="p-4 font-semibold text-gray-600 w-1/8">Total Price</th>
                                <th scope="col" class="p-4 text-center pr-5 font-semibold text-gray-600 w-1/6">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <tr v-for="booking in bookings" :key="booking.id"
                                class="transition-colors hover:bg-gray-50/50"
                                :class="booking.is_conflicted ? 'bg-red-50/40 hover:bg-red-50/80' : ''">
                                <td class="p-4 pl-5">
                                    <div class="font-semibold text-gray-900">
                                        {{ booking.user?.full_name || 'Unknown Guest' }}
                                    </div>
                                    
                                    <div v-if="booking.is_conflicted"
                                         class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-red-700 bg-red-100/80 px-2 py-0.5 rounded-md mt-1.5 uppercase tracking-wide">
                                        <i class="ti ti-alert-triangle text-xs"></i>
                                        Schedule Conflict
                                    </div>
                                </td>
                                <td class="p-4 text-gray-900">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium">{{ booking.room?.room_type?.name || '—' }}</span>
                                        <span class="inline-flex items-center self-start mt-1 text-[11px] font-bold px-2 py-0.5 rounded-md"
                                              :class="booking.is_conflicted ? 'text-red-700 bg-red-100/60' : 'text-teal-700 bg-teal-50'">
                                            Room #{{ booking.room?.room_number || 'N/A' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 text-gray-600 font-medium">{{ booking.start_at_formatted || '—' }}</td>
                                <td class="p-4 text-gray-600 font-medium">{{ booking.end_at_formatted || '—' }}</td>
                                <td class="p-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wider"
                                          :class="booking.is_conflicted ? 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-600/20' : statusClass(booking.status)">
                                        {{ booking.is_conflicted ? 'Conflict' : (tabs[booking.status] || booking.status.replace('_', ' ')) }}
                                    </span>
                                </td>
                                <td class="p-4 font-bold text-gray-900">₱{{ formatAmount(booking.total_amount) }}</td>
                                <td class="p-4 pr-5">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="viewDetails(booking)"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-gray-700 transition active:scale-95 cursor-pointer">
                                            <i class="ti ti-eye text-xs"></i>
                                            Details
                                        </button>
                                        
                                        <button v-if="booking.status === 'pending'"
                                                @click="approve(booking)"
                                                :disabled="processing === booking.id || booking.is_conflicted"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                            <i class="ti ti-circle-check text-xs"></i>
                                            Approve
                                        </button>
                                        <button v-if="booking.status === 'pending'"
                                                @click="decline(booking)"
                                                :disabled="processing === booking.id"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 transition active:scale-95 cursor-pointer">
                                            <i class="ti ti-x text-xs"></i>
                                            Decline
                                        </button>

                                        <button v-if="booking.status === 'approved'"
                                                @click="checkin(booking)"
                                                :disabled="processing === booking.id"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition active:scale-95 cursor-pointer">
                                            <i class="ti ti-login text-xs"></i>
                                            Check In
                                        </button>

                                        <button v-if="booking.status === 'checked_in'"
                                                @click="checkout(booking)"
                                                :disabled="processing === booking.id"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-purple-700 transition active:scale-95 cursor-pointer">
                                            <i class="ti ti-logout text-xs"></i>
                                            Check Out
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="bookings.length === 0">
                                <td colspan="7" class="p-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                            <i class="ti ti-calendar-off text-3xl text-gray-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">No bookings found</p>
                                            <p class="text-xs text-gray-400 mt-1">There are no records listed inside this status category tab layout.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Availability Matrix Calendar Modal -->
            <Teleport to="body">
                <div v-if="showCalendarModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 lg:p-8"
                     @click.self="closeCalendar">
                    <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeCalendar"></div>

                    <div class="relative w-full max-w-7xl max-h-[90vh] overflow-y-auto transform rounded-2xl bg-white p-5 sm:p-6 shadow-2xl border border-gray-100">
                        <div class="absolute top-4 right-4 z-20">
                            <button @click="closeCalendar"
                                    class="rounded-xl border border-gray-200 bg-white p-1.5 text-gray-400 shadow-sm hover:text-gray-700 hover:bg-gray-50 transition active:scale-95 cursor-pointer">
                                <i class="ti ti-x text-lg"></i>
                            </button>
                        </div>

                        <div class="mt-2">
                            <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Room Type Availability Matrix</h2>
                                    <p class="text-sm text-gray-500 mt-1">Real-time occupancy status for confirmed reservations.</p>
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
                                <p class="text-sm font-medium">Select a lodging variant above to populate the availability matrix grids.</p>
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
            </Teleport>

            <!-- Details Modal -->
            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm"
                     @click.self="closeModal">
                    <div class="relative min-h-[200px] max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl border border-gray-100">
                        <div v-if="selectedBooking" class="contents">
                            <!-- Modal Header -->
                            <div class="mb-5 flex items-start justify-between border-b border-gray-200 pb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 tracking-tight">
                                        Booking Record #{{ selectedBooking.id }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">Reservation Details</p>
                                </div>
                                <button @click="closeModal" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                                    <i class="ti ti-x text-lg"></i>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm text-gray-600 mb-5">
                                <!-- Stay Information -->
                                <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="ti ti-bed text-lg text-teal-600"></i>
                                        <h4 class="font-semibold text-gray-800">Stay Information</h4>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-500 font-medium">Status:</span>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wider"
                                                  :class="statusClass(selectedBooking.status)">
                                                {{ selectedBooking.status.replace('_', ' ') }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Room:</span>
                                            <span class="font-bold text-teal-800 bg-teal-50 px-2 py-0.5 rounded text-xs">#{{ selectedBooking.room?.room_number || 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Room Type:</span>
                                            <span class="text-gray-900 font-semibold text-right">{{ selectedBooking.room?.room_type?.name || selectedBooking.room_type || 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Floor:</span>
                                            <span class="text-gray-900 font-semibold">{{ selectedBooking.room?.floor || 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Capacity:</span>
                                            <span class="text-gray-900 font-semibold">{{ selectedBooking.room?.room_type?.capacity || 'N/A' }}</span>
                                        </div>
                                        <div class="border-t border-gray-200 pt-3 space-y-2">
                                            <div class="flex flex-col">
                                                <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">CHECK-IN</span>
                                                <span class="font-mono text-gray-800 font-semibold text-sm">{{ selectedBooking.start_at_formatted }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">CHECK-OUT</span>
                                                <span class="font-mono text-gray-600 font-medium text-sm">{{ selectedBooking.end_at_formatted }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guest Information -->
                                <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="ti ti-user text-lg text-teal-600"></i>
                                        <h4 class="font-semibold text-gray-800">Guest Information</h4>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium">Guest Name:</span>
                                            <span class="text-gray-900 font-bold text-base mt-0.5">{{ selectedBooking.user?.full_name || 'System Guest' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium">Email:</span>
                                            <span class="text-gray-700 font-semibold select-all font-mono text-xs break-all mt-0.5">{{ selectedBooking.user?.email || 'N/A' }}</span>
                                        </div>
                                        <div class="flex flex-col" v-if="selectedBooking.user?.phone">
                                            <span class="text-gray-500 font-medium">Phone:</span>
                                            <span class="text-gray-700 font-semibold font-mono text-xs mt-0.5">{{ selectedBooking.user?.phone }}</span>
                                        </div>
                                        <div class="flex flex-col" v-if="selectedBooking.user?.address">
                                            <span class="text-gray-500 font-medium">Address:</span>
                                            <span class="text-gray-700 font-semibold text-xs mt-0.5">{{ selectedBooking.user?.address }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guest Count -->
                                <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="ti ti-users text-lg text-teal-600"></i>
                                        <h4 class="font-semibold text-gray-800">Guest Composition</h4>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Adults:</span>
                                            <span class="text-gray-900 font-semibold">{{ selectedBooking.guests || 1 }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Child:</span>
                                            <span class="text-gray-900 font-semibold">{{ selectedBooking.has_child ? 1 : 0 }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="selectedBooking.child_age_group">
                                            <span class="text-gray-500 font-medium">Child Age Group:</span>
                                            <span class="text-gray-900 font-semibold">{{ selectedBooking.child_age_group }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">PWD:</span>
                                            <span class="text-gray-900 font-semibold">{{ selectedBooking.has_pwd ? 1 : 0 }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Senior:</span>
                                            <span class="text-gray-900 font-semibold">{{ selectedBooking.has_senior ? 1 : 0 }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Extra Beds:</span>
                                            <span class="text-gray-900 font-semibold">{{ selectedBooking.extra_beds || 0 }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment & Pricing -->
                                <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="ti ti-credit-card text-lg text-teal-600"></i>
                                        <h4 class="font-semibold text-gray-800">Payment & Pricing</h4>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Payment Method:</span>
                                            <span class="uppercase font-mono text-xs font-bold bg-white px-2 py-0.5 rounded border border-gray-200 text-gray-800">{{ selectedBooking.payment_method || 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="selectedBooking.payment_choice">
                                            <span class="text-gray-500 font-medium">Payment Choice:</span>
                                            <span class="uppercase font-mono text-xs font-semibold bg-white px-2 py-0.5 rounded border border-gray-200 text-gray-800">{{ selectedBooking.payment_choice }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Price at Booking:</span>
                                            <span class="text-gray-900 font-semibold">₱{{ formatAmount(selectedBooking.price_at_booking) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 font-medium">Total Amount:</span>
                                            <span class="text-emerald-600 font-bold">₱{{ formatAmount(selectedBooking.total_amount) }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="selectedBooking.amount_paid">
                                            <span class="text-gray-500 font-medium">Amount Paid:</span>
                                            <span class="text-blue-600 font-semibold">₱{{ formatAmount(selectedBooking.amount_paid) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action History -->
                                <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="ti ti-history text-lg text-teal-600"></i>
                                        <h4 class="font-semibold text-gray-800">Action History</h4>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Approved By:</span>
                                            <span class="text-gray-900 font-semibold mt-0.5">{{ selectedBooking.approved_by?.full_name || '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Declined By:</span>
                                            <span class="text-gray-900 font-semibold mt-0.5">{{ selectedBooking.rejected_by?.full_name || '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Checked In By:</span>
                                            <span class="text-gray-900 font-semibold mt-0.5">{{ selectedBooking.checked_in_by?.full_name || '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Checked Out By:</span>
                                            <span class="text-gray-900 font-semibold mt-0.5">{{ selectedBooking.checked_out_by?.full_name || '—' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timestamps -->
                                <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="ti ti-clock text-lg text-teal-600"></i>
                                        <h4 class="font-semibold text-gray-800">Timestamps</h4>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Created At:</span>
                                            <span class="text-gray-900 font-semibold font-mono text-xs mt-0.5">{{ toHumanReadableDate(selectedBooking.created_at) }}</span>
                                        </div>
                                        <div class="flex flex-col" v-if="selectedBooking.checked_in_at">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Checked In At:</span>
                                            <span class="text-gray-900 font-semibold font-mono text-xs mt-0.5">{{ toHumanReadableDate(selectedBooking.checked_in_at) }}</span>
                                        </div>
                                        <div class="flex flex-col" v-if="selectedBooking.checked_out_at">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Checked Out At:</span>
                                            <span class="text-gray-900 font-semibold font-mono text-xs mt-0.5">{{ toHumanReadableDate(selectedBooking.checked_out_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedBooking.message" class="mb-5 p-4 bg-blue-50/30 rounded-xl border border-blue-100">
                                <span class="text-blue-900 font-semibold uppercase text-xs tracking-wider block mb-1">Guest Notes:</span>
                                <span class="italic text-gray-600 font-medium">"{{ selectedBooking.message }}"</span>
                            </div>

                            <div v-if="selectedBooking.status === 'pending'" class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5">
                                <button @click="decline(selectedBooking)"
                                        :disabled="processing === selectedBooking.id"
                                        class="rounded-lg border border-red-200 bg-white px-5 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition disabled:opacity-50 cursor-pointer">
                                    {{ processing === selectedBooking.id ? 'Processing...' : 'Decline' }}
                                </button>
                                <button @click="approve(selectedBooking)"
                                        :disabled="processing === selectedBooking.id || selectedBooking.is_conflicted"
                                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                    <i class="ti ti-circle-check"></i>
                                    {{ processing === selectedBooking.id ? 'Processing...' : 'Approve' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>