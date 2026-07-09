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
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                            <i class="ti ti-building-skyscraper text-lg text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400">Total Rooms</p>
                            <p class="text-xl font-bold text-gray-900">{{ props.roomStatus?.total || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50">
                            <i class="ti ti-users text-lg text-rose-500"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400">Occupied</p>
                            <p class="text-xl font-bold text-gray-900">{{ props.roomStatus?.occupied || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50">
                            <i class="ti ti-door-enter text-lg text-teal-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400">Available</p>
                            <p class="text-xl font-bold text-gray-900">{{ props.roomStatus?.available || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                            <i class="ti ti-spray text-lg text-amber-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400">Needs Cleaning</p>
                            <p class="text-xl font-bold text-gray-900">{{ props.roomStatus?.needsCleaning || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50">
                            <i class="ti ti-calendar-time text-lg text-violet-600"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-400">Pending</p>
                            <p class="text-xl font-bold text-gray-900">{{ props.pendingCount || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
                <span class="w-20 whitespace-nowrap text-xs font-bold uppercase tracking-wider text-gray-400">Occupancy</span>
                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full transition-all duration-700"
                         :class="(props.roomStatus?.occupancyPct || 0) > 70 ? 'bg-rose-500' : (props.roomStatus?.occupancyPct || 0) > 40 ? 'bg-amber-500' : 'bg-teal-500'"
                         :style="{ width: (props.roomStatus?.occupancyPct || 0) + '%' }"></div>
                </div>
                <span class="text-sm font-bold tabular-nums text-gray-800">{{ props.roomStatus?.occupancyPct || 0 }}%</span>
                <span class="text-xs font-medium text-gray-400 bg-gray-50 px-2 py-1 rounded-md">{{ props.roomStatus?.occupied || 0 }} / {{ props.roomStatus?.total || 0 }}</span>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
                    <h2 class="mb-4 text-sm font-bold text-gray-800 uppercase tracking-wide">Currently Occupied Rooms</h2>
                    <div class="space-y-2" v-if="props.occupiedRooms?.length">
                        <div v-for="room in props.occupiedRooms" :key="room.id"
                             class="flex items-center gap-3 rounded-xl px-2 py-3 hover:bg-gray-50/80">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-rose-50 border border-rose-100">
                                <span class="text-xs font-bold text-rose-500">{{ (room.guest_name || '?')[0] }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-800">{{ room.guest_name }}</p>
                                <p class="truncate text-xs text-gray-400 font-medium">{{ room.room_type }} · Room {{ room.room_number }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right text-xs">
                                <p class="font-semibold text-gray-700">{{ fmtDate(room.start_at) }} → {{ fmtDate(room.end_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-12 text-gray-400 italic text-sm">No rooms occupied.</div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
                    <h2 class="mb-4 text-sm font-bold text-gray-800 uppercase tracking-wide">Upcoming Reservations</h2>
                    <div class="space-y-2" v-if="props.upcomingBookings?.length">
                        <div v-for="booking in props.upcomingBookings" :key="booking.id"
                             class="flex items-center gap-3 rounded-xl px-2 py-3 hover:bg-gray-50/80">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-teal-50 border border-teal-100">
                                <span class="text-xs font-bold text-teal-600">{{ (booking.guest_name || '?')[0] }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-800">{{ booking.guest_name }}</p>
                                <p class="truncate text-xs text-gray-400 font-medium">{{ booking.room_type }} · Room {{ booking.room_number }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right text-xs">
                                <p class="font-semibold text-gray-700">{{ fmtDate(booking.start_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-12 text-gray-400 italic text-sm">No upcoming arrivals.</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>