<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()
const props = page.props
const showModal = ref(false)
const selected = ref(null)

const archived = props.archivedBookings || []

function viewDetails(booking) {
    selected.value = booking
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    selected.value = null
}

function formatDate(d) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatDateTime(d) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatAmount(a) {
    if (!a) return '0.00'
    return Number(a).toLocaleString('en-US', { minimumFractionDigits: 2 })
}

function statusClass(status) {
    const map = {
        checked_out: 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-500/10',
    }
    return map[status] || 'bg-gray-100 text-gray-600'
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-(screen-2xl) mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <!-- Page Header -->
            <div class="border-b border-gray-200 pb-4">
                <h1 class="text-2xl font-bold text-gray-900">Archived Bookings</h1>
                <p class="text-sm text-gray-500 mt-1">Review completed and archived historical reservation records.</p>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100">
                            <i class="ti ti-archive text-xl text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Archived</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ archived.length }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50">
                            <i class="ti ti-circle-check text-xl text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</p>
                            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ archived.filter(b => b.status === 'checked_out').length }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50">
                            <i class="ti ti-currency-peso text-xl text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">₱{{ formatAmount(archived.reduce((sum, b) => sum + (Number(b.total_amount) || 0), 0)) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archived Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[1000px] w-full text-sm text-left">
                        <thead class="border-b border-gray-200 bg-gray-50/80">
                            <tr>
                                <th class="p-4 pl-5 font-semibold text-gray-600 w-1/4">Guest</th>
                                <th class="p-4 font-semibold text-gray-600 w-1/6">Room</th>
                                <th class="p-4 font-semibold text-gray-600 w-1/6">Check-In</th>
                                <th class="p-4 font-semibold text-gray-600 w-1/6">Check-Out</th>
                                <th class="p-4 font-semibold text-gray-600 w-1/8">Status</th>
                                <th class="p-4 font-semibold text-gray-600 w-1/8">Total</th>
                                <th class="p-4 pr-5 text-center font-semibold text-gray-600 w-1/6">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="b in archived" :key="b.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4 pl-5">
                                    <div class="font-semibold text-gray-900">
                                        {{ b.user?.full_name || (b.guest_fname + ' ' + b.guest_lname) || 'Archived Guest' }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-1 text-xs font-bold text-teal-700">
                                        #{{ b.room?.room_number || b.room_number || 'N/A' }}
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600">{{ formatDate(b.start_at) }}</td>
                                <td class="p-4 text-gray-600">{{ formatDate(b.end_at) }}</td>
                                <td class="p-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wider"
                                          :class="statusClass(b.status)">
                                        {{ b.status || 'archived' }}
                                    </span>
                                </td>
                                <td class="p-4 font-bold text-gray-900">₱{{ formatAmount(b.total_amount) }}</td>
                                <td class="p-4 pr-5 text-center">
                                    <button @click="viewDetails(b)"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-800 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-700 transition shadow-sm cursor-pointer">
                                        <i class="ti ti-eye text-xs"></i>
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="archived.length === 0">
                                <td colspan="7" class="p-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                            <i class="ti ti-archive-off text-3xl text-gray-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">No archived records found</p>
                                            <p class="text-xs text-gray-400 mt-1">No historical booking records are available at this time.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Details Modal -->
            <Teleport to="body">
                <div v-if="showModal && selected" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm"
                     @click.self="closeModal">
                    <div class="relative min-h-[200px] max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl border border-gray-100">
                        <!-- Modal Header -->
                        <div class="mb-5 flex items-start justify-between border-b border-gray-200 pb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Archived Booking #{{ selected.id }}</h3>
                                <p class="text-xs text-gray-500 mt-1">Historical Reservation Details</p>
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
                                              :class="statusClass(selected.status)">
                                            {{ selected.status || 'archived' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Room:</span>
                                        <span class="font-bold text-teal-800 bg-teal-50 px-2 py-0.5 rounded text-xs">#{{ selected.room?.room_number || selected.room_number || 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Room Type:</span>
                                        <span class="text-gray-900 font-semibold text-right">{{ selected.room?.room_type?.name || selected.room_type_name || 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Floor:</span>
                                        <span class="text-gray-900 font-semibold">{{ selected.room?.floor || selected.room_floor || 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Capacity:</span>
                                        <span class="text-gray-900 font-semibold">{{ selected.room?.room_type?.capacity || selected.room_capacity || 'N/A' }}</span>
                                    </div>
                                    <div class="border-t border-gray-200 pt-3 space-y-2">
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">CHECK-IN</span>
                                            <span class="font-mono text-gray-800 font-semibold text-sm">{{ formatDateTime(selected.start_at) }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">CHECK-OUT</span>
                                            <span class="font-mono text-gray-600 font-medium text-sm">{{ formatDateTime(selected.end_at) }}</span>
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
                                        <span class="text-gray-900 font-bold text-base mt-0.5">{{ selected.user?.full_name || (selected.guest_fname + ' ' + selected.guest_lname) || 'Archived Guest' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-500 font-medium">Email:</span>
                                        <span class="text-gray-700 font-semibold select-all font-mono text-xs break-all mt-0.5">{{ selected.user?.email || selected.guest_email || 'N/A' }}</span>
                                    </div>
                                    <div class="flex flex-col" v-if="selected.user?.phone">
                                        <span class="text-gray-500 font-medium">Phone:</span>
                                        <span class="text-gray-700 font-semibold font-mono text-xs mt-0.5">{{ selected.user?.phone }}</span>
                                    </div>
                                    <div class="flex flex-col" v-if="selected.user?.address">
                                        <span class="text-gray-500 font-medium">Address:</span>
                                        <span class="text-gray-700 font-semibold text-xs mt-0.5">{{ selected.user?.address }}</span>
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
                                        <span class="text-gray-900 font-semibold">{{ selected.guests || 1 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Child:</span>
                                        <span class="text-gray-900 font-semibold">{{ selected.has_child ? 1 : 0 }}</span>
                                    </div>
                                    <div class="flex justify-between" v-if="selected.child_age_group">
                                        <span class="text-gray-500 font-medium">Child Age Group:</span>
                                        <span class="text-gray-900 font-semibold">{{ selected.child_age_group }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">PWD:</span>
                                        <span class="text-gray-900 font-semibold">{{ selected.has_pwd ? 1 : 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Senior:</span>
                                        <span class="text-gray-900 font-semibold">{{ selected.has_senior ? 1 : 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Extra Beds:</span>
                                        <span class="text-gray-900 font-semibold">{{ selected.extra_beds || 0 }}</span>
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
                                        <span class="uppercase font-mono text-xs font-bold bg-white px-2 py-0.5 rounded border border-gray-200 text-gray-800">{{ selected.payment_method || 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Price at Booking:</span>
                                        <span class="text-gray-900 font-semibold">₱{{ formatAmount(selected.price_at_booking) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 font-medium">Total Amount:</span>
                                        <span class="text-emerald-600 font-bold">₱{{ formatAmount(selected.total_amount) }}</span>
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
                                        <span class="text-gray-900 font-semibold mt-0.5">{{ selected.approved_by?.full_name || '—' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Declined By:</span>
                                        <span class="text-gray-900 font-semibold mt-0.5">{{ selected.rejected_by?.full_name || '—' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Checked In By:</span>
                                        <span class="text-gray-900 font-semibold mt-0.5">{{ selected.checked_in_by?.full_name || '—' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Checked Out By:</span>
                                        <span class="text-gray-900 font-semibold mt-0.5">{{ selected.checked_out_by?.full_name || '—' }}</span>
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
                                        <span class="text-gray-900 font-semibold font-mono text-xs mt-0.5">{{ formatDateTime(selected.created_at) }}</span>
                                    </div>
                                    <div class="flex flex-col" v-if="selected.checked_in_at">
                                        <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Checked In At:</span>
                                        <span class="text-gray-900 font-semibold font-mono text-xs mt-0.5">{{ formatDateTime(selected.checked_in_at) }}</span>
                                    </div>
                                    <div class="flex flex-col" v-if="selected.checked_out_at">
                                        <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Checked Out At:</span>
                                        <span class="text-gray-900 font-semibold font-mono text-xs mt-0.5">{{ formatDateTime(selected.checked_out_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="selected.message" class="mb-5 p-4 bg-blue-50/30 rounded-xl border border-blue-100">
                            <span class="text-blue-900 font-semibold uppercase text-xs tracking-wider block mb-1">Guest Notes:</span>
                            <span class="italic text-gray-600 font-medium">"{{ selected.message }}"</span>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>