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
        checked_out: 'badge-neutral',
    }
    return map[status] || 'badge-neutral'
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-(screen-2xl) mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="text-heading-1">Archived Bookings</h1>
                <p class="text-body text-gray-500 mt-1">Review completed and archived historical reservation records.</p>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="stats-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100">
                            <i class="ti ti-archive text-xl text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-label">Total Archived</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ archived.length }}</p>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50">
                            <i class="ti ti-circle-check text-xl text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-label">Completed</p>
                            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ archived.filter(b => b.status === 'checked_out').length }}</p>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50">
                            <i class="ti ti-currency-peso text-xl text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-label">Total Revenue</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">₱{{ formatAmount(archived.reduce((sum, b) => sum + (Number(b.total_amount) || 0), 0)) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archived Table -->
            <div class="table-container">
                <div class="overflow-x-auto">
                    <table class="min-w-[1000px] w-full data-table">
                        <thead>
                            <tr>
                                <th class="w-2/5">Guest</th>
                                <th class="w-1/6">Room</th>
                                <th class="w-1/6">Check-In</th>
                                <th class="w-1/6">Check-Out</th>
                                <th class="w-1/8">Status</th>
                                <th class="w-1/8">Total</th>
                                <th class="w-1/6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="b in archived" :key="b.id" class="transition-colors hover:bg-gray-50/50">
                                <td>
                                    <div class="font-semibold text-gray-900">
                                        {{ b.user?.full_name || (b.guest_fname + ' ' + b.guest_lname) || 'Archived Guest' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">
                                        #{{ b.room?.room_number || b.room_number || 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-caption font-medium">{{ formatDate(b.start_at) }}</td>
                                <td class="text-caption font-medium">{{ formatDate(b.end_at) }}</td>
                                <td>
                                    <span class="badge" :class="statusClass(b.status)">
                                        {{ b.status || 'archived' }}
                                    </span>
                                </td>
                                <td class="font-bold text-gray-900">₱{{ formatAmount(b.total_amount) }}</td>
                                <td class="text-center">
                                    <button @click="viewDetails(b)"
                                            class="btn btn-primary">
                                        <i class="ti ti-eye text-xs"></i>
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="archived.length === 0">
                                <td colspan="7" class="p-12">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="ti ti-archive-off text-3xl text-gray-400"></i>
                                        </div>
                                        <p class="text-body font-medium text-gray-500">No archived records found</p>
                                        <p class="text-caption mt-1">No historical booking records are available at this time.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Details Modal -->
            <Teleport to="body">
                <div v-if="showModal && selected" class="modal-backdrop" @click.self="closeModal">
                    <div class="modal-container" @click.self="closeModal">
                        <div class="modal-panel w-full max-w-4xl">
                            <!-- Modal Header -->
                            <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                                <div>
                                    <h3 class="text-heading-2">Archived Booking #{{ selected.id }}</h3>
                                    <p class="text-caption mt-1">Historical Reservation Details</p>
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
                                            <span class="badge" :class="statusClass(selected.status)">
                                                {{ selected.status || 'archived' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Room:</span>
                                            <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-0.5 text-xs font-bold text-teal-700">#{{ selected.room?.room_number || selected.room_number || 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Room Type:</span>
                                            <span class="text-gray-900 font-medium">{{ selected.room?.room_type?.name || selected.room_type_name || 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Floor:</span>
                                            <span class="text-gray-900 font-medium">{{ selected.room?.floor || selected.room_floor || 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Capacity:</span>
                                            <span class="text-gray-900 font-medium">{{ selected.room?.room_type?.capacity || selected.room_capacity || 'N/A' }}</span>
                                        </div>
                                        <div class="border-t border-gray-200 pt-3 space-y-2">
                                            <div class="flex flex-col">
                                                <span class="text-label">CHECK-IN</span>
                                                <span class="font-mono text-gray-800 font-medium text-sm">{{ formatDateTime(selected.start_at) }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-label">CHECK-OUT</span>
                                                <span class="font-mono text-gray-600 text-sm">{{ formatDateTime(selected.end_at) }}</span>
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
                                            <span class="text-gray-900 font-bold text-base mt-0.5">{{ selected.user?.full_name || (selected.guest_fname + ' ' + selected.guest_lname) || 'Archived Guest' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-label">Email:</span>
                                            <span class="text-gray-700 font-medium select-all font-mono text-xs break-all mt-0.5">{{ selected.user?.email || selected.guest_email || 'N/A' }}</span>
                                        </div>
                                        <div class="flex flex-col" v-if="selected.user?.phone">
                                            <span class="text-label">Phone:</span>
                                            <span class="text-gray-700 font-medium font-mono text-xs mt-0.5">{{ selected.user?.phone }}</span>
                                        </div>
                                        <div class="flex flex-col" v-if="selected.user?.address">
                                            <span class="text-label">Address:</span>
                                            <span class="text-gray-700 font-medium text-xs mt-0.5">{{ selected.user?.address }}</span>
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
                                            <span class="text-gray-900 font-medium">{{ selected.guests || 1 }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Child:</span>
                                            <span class="text-gray-900 font-medium">{{ selected.has_child ? 1 : 0 }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="selected.child_age_group">
                                            <span class="text-label">Child Age Group:</span>
                                            <span class="text-gray-900 font-medium">{{ selected.child_age_group }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">PWD:</span>
                                            <span class="text-gray-900 font-medium">{{ selected.has_pwd ? 1 : 0 }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Senior:</span>
                                            <span class="text-gray-900 font-medium">{{ selected.has_senior ? 1 : 0 }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Extra Beds:</span>
                                            <span class="text-gray-900 font-medium">{{ selected.extra_beds || 0 }}</span>
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
                                            <span class="uppercase font-mono text-xs font-bold bg-white px-2 py-0.5 rounded border border-gray-200 text-gray-800">{{ selected.payment_method || 'N/A' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Price at Booking:</span>
                                            <span class="text-gray-900 font-medium">₱{{ formatAmount(selected.price_at_booking) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-label">Total Amount:</span>
                                            <span class="text-emerald-600 font-bold">₱{{ formatAmount(selected.total_amount) }}</span>
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
                                            <span class="text-gray-900 font-medium mt-0.5">{{ selected.approved_by?.full_name || '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-label">Declined By:</span>
                                            <span class="text-gray-900 font-medium mt-0.5">{{ selected.rejected_by?.full_name || '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-label">Checked In By:</span>
                                            <span class="text-gray-900 font-medium mt-0.5">{{ selected.checked_in_by?.full_name || '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-label">Checked Out By:</span>
                                            <span class="text-gray-900 font-medium mt-0.5">{{ selected.checked_out_by?.full_name || '—' }}</span>
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
                                            <span class="text-gray-900 font-medium font-mono text-xs mt-0.5">{{ formatDateTime(selected.created_at) }}</span>
                                        </div>
                                        <div class="flex flex-col" v-if="selected.checked_in_at">
                                            <span class="text-label">Checked In At:</span>
                                            <span class="text-gray-900 font-medium font-mono text-xs mt-0.5">{{ formatDateTime(selected.checked_in_at) }}</span>
                                        </div>
                                        <div class="flex flex-col" v-if="selected.checked_out_at">
                                            <span class="text-label">Checked Out At:</span>
                                            <span class="text-gray-900 font-medium font-mono text-xs mt-0.5">{{ formatDateTime(selected.checked_out_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selected.message" class="mx-6 mb-6 p-4 bg-blue-50/30 rounded-xl border border-blue-100">
                                <span class="text-blue-900 font-semibold text-label block mb-1">Guest Notes:</span>
                                <span class="italic text-gray-600 font-medium">"{{ selected.message }}"</span>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>