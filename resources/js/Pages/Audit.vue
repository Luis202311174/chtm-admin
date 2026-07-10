<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()
const props = page.props
const activeTab = ref(props.tab || 'sales')

const summary = computed(() => props.summary || {})
const guestStats = computed(() => props.guestStats || {})
const auditLogs = computed(() => props.auditLogs || [])
const archivedRows = computed(() => props.archivedRows || [])

function switchTab(tab) {
    activeTab.value = tab
    router.get('/audit', { tab, period: props.period, year: props.year }, { preserveState: true, replace: true })
}

function formatAmount(a) {
    if (!a) return '0.00'
    return Number(a).toLocaleString('en-US', { minimumFractionDigits: 2 })
}

function formatDate(d) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="page-header">
                <h1 class="text-heading-1">Audit & Reports</h1>
                <p class="text-body text-gray-500">{{ props.dateLabel || 'Annual Report' }}</p>
            </div>

            <div class="tab-group">
                <button @click="switchTab('sales')" 
                        class="tab-button" 
                        :class="activeTab === 'sales' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-chart-bar text-sm"></i>
                    Sales
                </button>
                <button @click="switchTab('guests')" 
                        class="tab-button" 
                        :class="activeTab === 'guests' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-users text-sm"></i>
                    Guests
                </button>
                <button @click="switchTab('activity')" 
                        class="tab-button" 
                        :class="activeTab === 'activity' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-activity text-sm"></i>
                    Activity
                </button>
                <button @click="switchTab('archived')" 
                        class="tab-button" 
                        :class="activeTab === 'archived' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-archive text-sm"></i>
                    Archived
                </button>
            </div>

            <!-- Sales Tab -->
            <div v-if="activeTab === 'sales'" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stats-card">
                    <p class="text-label">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">₱{{ formatAmount(summary.total_revenue) }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">Cash</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">₱{{ formatAmount(summary.cash_revenue) }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">GCash</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">₱{{ formatAmount(summary.gcash_revenue) }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">Occupancy Rate</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ summary.occupancy_rate || 0 }}%</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">Bookings</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ summary.total_bookings || 0 }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">Avg Stay</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ summary.avg_stay_nights || 0 }} nights</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">Top Room Type</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ summary.top_room_type || '—' }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">Pending</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ summary.pending || 0 }}</p>
                </div>
            </div>

            <!-- Guests Tab -->
            <div v-if="activeTab === 'guests'" class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="stats-card">
                    <p class="text-label">Total Guests</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ guestStats.total_guests || 0 }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">With Children</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ guestStats.with_children || 0 }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">PWD</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ guestStats.with_pwd || 0 }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">Senior</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ guestStats.with_senior || 0 }}</p>
                </div>
                <div class="stats-card">
                    <p class="text-label">Extra Beds</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ guestStats.extra_beds || 0 }}</p>
                </div>
            </div>

            <!-- Activity Tab -->
            <div v-if="activeTab === 'activity'" class="table-container">
                <div class="overflow-x-auto">
                    <table class="min-w-[600px] w-full data-table">
                        <thead>
                            <tr>
                                <th class="w-1/3">Action</th>
                                <th class="w-1/3">Entity</th>
                                <th class="w-1/3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in auditLogs" :key="log.id" class="transition-colors hover:bg-gray-50/50">
                                <td class="font-medium">{{ log.action }}</td>
                                <td class="text-gray-500">{{ log.entity_type }} #{{ log.entity_id }}</td>
                                <td class="text-gray-500">{{ formatDate(log.created_at) }}</td>
                            </tr>
                            <tr v-if="auditLogs.length === 0">
                                <td colspan="3" class="p-12">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="ti ti-activity-off text-3xl text-gray-300"></i>
                                        </div>
                                        <p class="text-body font-medium text-gray-500">No activity found</p>
                                        <p class="text-caption mt-1">No audit logs are available for this period.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Archived Tab -->
            <div v-if="activeTab === 'archived'" class="table-container">
                <div class="overflow-x-auto">
                    <table class="min-w-[600px] w-full data-table">
                        <thead>
                            <tr>
                                <th class="w-1/3">Guest</th>
                                <th class="w-1/3">Total</th>
                                <th class="w-1/3">Check-Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in archivedRows" :key="row.id" class="transition-colors hover:bg-gray-50/50">
                                <td class="font-semibold text-gray-900">{{ row.guest_fname }} {{ row.guest_lname }}</td>
                                <td class="font-bold text-gray-900">₱{{ formatAmount(row.total_amount) }}</td>
                                <td class="text-gray-500">{{ formatDate(row.checked_out_at) }}</td>
                            </tr>
                            <tr v-if="archivedRows.length === 0">
                                <td colspan="3" class="p-12">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="ti ti-archive-off text-3xl text-gray-300"></i>
                                        </div>
                                        <p class="text-body font-medium text-gray-500">No records found</p>
                                        <p class="text-caption mt-1">No archived booking records available.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>