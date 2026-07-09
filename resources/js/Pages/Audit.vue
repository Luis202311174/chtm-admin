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
            <div>
                <h1 class="text-xl font-bold text-gray-900">Audit & Reports</h1>
                <p class="text-xs text-gray-500">{{ props.dateLabel || 'Annual Report' }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-1 shadow-xs inline-flex">
                <button @click="switchTab('sales')" class="px-4 py-2 text-xs font-bold rounded-lg transition" :class="activeTab === 'sales' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Sales</button>
                <button @click="switchTab('guests')" class="px-4 py-2 text-xs font-bold rounded-lg transition" :class="activeTab === 'guests' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Guests</button>
                <button @click="switchTab('activity')" class="px-4 py-2 text-xs font-bold rounded-lg transition" :class="activeTab === 'activity' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Activity</button>
                <button @click="switchTab('archived')" class="px-4 py-2 text-xs font-bold rounded-lg transition" :class="activeTab === 'archived' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Archived</button>
            </div>

            <!-- Sales Tab -->
            <div v-if="activeTab === 'sales'" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Total Revenue</p>
                    <p class="text-xl font-bold text-gray-900">₱{{ formatAmount(summary.total_revenue) }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Cash</p>
                    <p class="text-xl font-bold text-gray-900">₱{{ formatAmount(summary.cash_revenue) }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">GCash</p>
                    <p class="text-xl font-bold text-gray-900">₱{{ formatAmount(summary.gcash_revenue) }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Occupancy Rate</p>
                    <p class="text-xl font-bold text-gray-900">{{ summary.occupancy_rate || 0 }}%</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Bookings</p>
                    <p class="text-xl font-bold text-gray-900">{{ summary.total_bookings || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Avg Stay</p>
                    <p class="text-xl font-bold text-gray-900">{{ summary.avg_stay_nights || 0 }} nights</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Top Room Type</p>
                    <p class="text-xl font-bold text-gray-900">{{ summary.top_room_type || '—' }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Pending</p>
                    <p class="text-xl font-bold text-amber-600">{{ summary.pending || 0 }}</p>
                </div>
            </div>

            <!-- Guests Tab -->
            <div v-if="activeTab === 'guests'" class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Total Guests</p>
                    <p class="text-xl font-bold text-gray-900">{{ guestStats.total_guests || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">With Children</p>
                    <p class="text-xl font-bold text-gray-900">{{ guestStats.with_children || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">PWD</p>
                    <p class="text-xl font-bold text-gray-900">{{ guestStats.with_pwd || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Senior</p>
                    <p class="text-xl font-bold text-gray-900">{{ guestStats.with_senior || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <p class="text-xs text-gray-400">Extra Beds</p>
                    <p class="text-xl font-bold text-gray-900">{{ guestStats.extra_beds || 0 }}</p>
                </div>
            </div>

            <!-- Activity Tab -->
            <div v-if="activeTab === 'activity'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-[600px] w-full text-xs text-left">
                        <thead class="border-b border-gray-200 bg-gray-50/70 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                            <tr><th class="p-3 pl-5">Action</th><th class="p-3">Entity</th><th class="p-3">Date</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-gray-50/40">
                                <td class="p-3 pl-5 font-semibold">{{ log.action }}</td>
                                <td class="p-3 text-gray-500">{{ log.entity_type }} #{{ log.entity_id }}</td>
                                <td class="p-3 text-gray-500">{{ formatDate(log.created_at) }}</td>
                            </tr>
                            <tr v-if="auditLogs.length === 0"><td colspan="3" class="p-12 text-center text-gray-400">No activity found.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Archived Tab -->
            <div v-if="activeTab === 'archived'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-[600px] w-full text-xs text-left">
                        <thead class="border-b border-gray-200 bg-gray-50/70 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                            <tr><th class="p-3 pl-5">Guest</th><th class="p-3">Total</th><th class="p-3">Check-Out</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in archivedRows" :key="row.id" class="hover:bg-gray-50/40">
                                <td class="p-3 pl-5 font-semibold">{{ row.guest_fname }} {{ row.guest_lname }}</td>
                                <td class="p-3 font-bold">₱{{ formatAmount(row.total_amount) }}</td>
                                <td class="p-3 text-gray-500">{{ formatDate(row.checked_out_at) }}</td>
                            </tr>
                            <tr v-if="archivedRows.length === 0"><td colspan="3" class="p-12 text-center text-gray-400">No records found.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>