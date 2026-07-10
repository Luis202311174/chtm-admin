<script setup>
import AppLayout from '../Layouts/AppLayout.vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const props = page.props

function fmtDate(date) {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Stats cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <div class="stats-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                            <i class="ti ti-building-skyscraper text-lg text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-label">Total Rooms</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.roomStatus?.total || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50">
                            <i class="ti ti-users text-lg text-rose-500"></i>
                        </div>
                        <div>
                            <p class="text-label">Occupied</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.roomStatus?.occupied || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50">
                            <i class="ti ti-door-enter text-lg text-teal-600"></i>
                        </div>
                        <div>
                            <p class="text-label">Available</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.roomStatus?.available || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                            <i class="ti ti-spray text-lg text-amber-600"></i>
                        </div>
                        <div>
                            <p class="text-label">Needs Cleaning</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.roomStatus?.needsCleaning || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50">
                            <i class="ti ti-calendar-time text-lg text-violet-600"></i>
                        </div>
                        <div>
                            <p class="text-label">Pending</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ props.pendingCount || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                <span class="w-20 whitespace-nowrap text-label">Occupancy</span>
                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full transition-all duration-700"
                         :class="(props.roomStatus?.occupancyPct || 0) > 70 ? 'bg-rose-500' : (props.roomStatus?.occupancyPct || 0) > 40 ? 'bg-amber-500' : 'bg-teal-500'"
                         :style="{ width: (props.roomStatus?.occupancyPct || 0) + '%' }"></div>
                </div>
                <span class="text-sm font-bold tabular-nums text-gray-800">{{ props.roomStatus?.occupancyPct || 0 }}%</span>
                <span class="text-xs font-medium text-gray-500 bg-gray-50 px-2.5 py-1 rounded-lg">{{ props.roomStatus?.occupied || 0 }} / {{ props.roomStatus?.total || 0 }}</span>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="table-container">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-heading-3">Currently Occupied Rooms</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[800px] w-full data-table">
                            <thead>
                                <tr>
                                    <th class="w-2/5">Guest</th>
                                    <th class="w-1/4">Room</th>
                                    <th class="w-1/4">Stay Period</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="room in props.occupiedRooms" :key="room.id" class="transition-colors hover:bg-gray-50/50">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-rose-50 border border-rose-100">
                                                <span class="text-xs font-bold text-rose-500">{{ (room.guest_name || '?')[0] }}</span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-body font-semibold text-gray-900">{{ room.guest_name }}</p>
                                                <p class="truncate text-caption font-medium">{{ room.room_type }} · Room {{ room.room_number }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">
                                            #{{ room.room_number }}
                                        </span>
                                    </td>
                                    <td class="text-caption font-medium">
                                        {{ fmtDate(room.start_at) }} → {{ fmtDate(room.end_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!props.occupiedRooms?.length">
                                    <td colspan="3" class="p-12">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="ti ti-bed-off text-3xl text-gray-300"></i>
                                            </div>
                                            <p class="text-body font-medium text-gray-500">No rooms occupied</p>
                                            <p class="text-caption mt-1">All rooms are currently available</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-container">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-heading-3">Upcoming Reservations</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[800px] w-full data-table">
                            <thead>
                                <tr>
                                    <th class="w-2/5">Guest</th>
                                    <th class="w-1/4">Room</th>
                                    <th class="w-1/4">Check-In</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="booking in props.upcomingBookings" :key="booking.id" class="transition-colors hover:bg-gray-50/50">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-teal-50 border border-teal-100">
                                                <span class="text-xs font-bold text-teal-600">{{ (booking.guest_name || '?')[0] }}</span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-body font-semibold text-gray-900">{{ booking.guest_name }}</p>
                                                <p class="truncate text-caption font-medium">{{ booking.room_type }} · Room {{ booking.room_number }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">
                                            #{{ booking.room_number }}
                                        </span>
                                    </td>
                                    <td class="text-caption font-medium">
                                        {{ fmtDate(booking.start_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!props.upcomingBookings?.length">
                                    <td colspan="3" class="p-12">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="ti ti-calendar-off text-3xl text-gray-300"></i>
                                            </div>
                                            <p class="text-body font-medium text-gray-500">No upcoming arrivals</p>
                                            <p class="text-caption mt-1">No reservations scheduled for future dates</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>