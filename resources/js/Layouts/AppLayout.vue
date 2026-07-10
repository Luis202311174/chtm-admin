<script setup>
import { ref, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const sidebarCollapsed = ref(localStorage.getItem('chtm_sidebar_collapsed') === '1')
const mobileOpen = ref(false)

const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: 'ti-layout-dashboard', route: 'dashboard' },
    { id: 'frontoffice', label: 'Front Office', icon: 'ti-building-store', route: 'frontoffice' },
    { id: 'reservation', label: 'Reservation', icon: 'ti-calendar-stats', route: 'reservation' },
    { id: 'archived', label: 'Archived', icon: 'ti-archive', route: 'archived' },
    { id: 'room', label: 'Room', icon: 'ti-building', route: 'room' },
    { id: 'audit', label: 'Audit & Reports', icon: 'ti-report-analytics', route: 'audit' },
    { id: 'settings', label: 'System Settings', icon: 'ti-settings', route: 'settings' },
]

function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem('chtm_sidebar_collapsed', sidebarCollapsed.value ? '1' : '0')
}

function isActive(item) {
    return page.component?.startsWith(item.id.charAt(0).toUpperCase() + item.id.slice(1))
}

// Track previous component for caching sidebar state
const prevComponent = ref(null)
onMounted(() => {
    prevComponent.value = page.component
})
</script>

<template>
    <div class="flex h-screen w-screen overflow-hidden bg-gray-50">
        <!-- Mobile overlay -->
        <div v-if="mobileOpen" class="md:hidden fixed inset-0 z-50">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-xs" @click="mobileOpen = false"></div>
            <aside class="fixed bottom-0 left-0 top-0 w-64 bg-gradient-to-b from-teal-950 to-teal-900 text-white shadow-2xl flex flex-col z-10">
                <div class="flex items-center justify-between border-b border-white/5 p-3.5">
                    <div>
                        <h1 class="text-base font-black tracking-tight">CHTM RRS</h1>
                        <p class="text-[10px] text-teal-300/80 font-bold uppercase tracking-wider">Hotel Management</p>
                    </div>
                    <button @click="mobileOpen = false" class="text-white/40 hover:text-white p-1 rounded-lg transition">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>
                <nav class="flex-1 space-y-0.5 p-2 overflow-y-auto">
                    <Link v-for="item in menuItems" :key="item.id"
                          :href="route(item.route)"
                          replace
                          @click="mobileOpen = false"
                          class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-bold transition-all duration-150"
                          :class="isActive(item) ? 'bg-gradient-to-r from-pink-500 to-pink-600 text-white shadow-xs' : 'text-teal-100 hover:bg-teal-800/40 hover:text-white'">
                        <i :class="`ti ${item.icon} text-sm`"></i>
                        <span>{{ item.label }}</span>
                    </Link>
                </nav>
            </aside>
        </div>

        <!-- Desktop sidebar -->
        <aside class="hidden md:flex flex-col h-screen bg-gradient-to-b from-teal-950 to-teal-900 text-white shadow-xs flex-shrink-0 transition-all duration-200"
               :class="sidebarCollapsed ? 'w-14' : 'w-56'">
            <div class="flex items-center justify-between border-b border-white/5 p-3 h-14 flex-shrink-0">
                <div v-show="!sidebarCollapsed" class="min-w-0 pl-1">
                    <h1 class="text-sm font-black tracking-tight truncate">CHTM RRS</h1>
                    <p class="text-[10px] text-teal-300/80 font-bold uppercase tracking-wider truncate">Hotel Management</p>
                </div>
                <div v-show="sidebarCollapsed" class="mx-auto text-teal-300">
                    <i class="ti ti-building-skyscraper text-lg"></i>
                </div>
                <button @click="toggleSidebar()" class="text-white/40 hover:text-white p-1 rounded-md transition flex-shrink-0">
                    <i v-show="!sidebarCollapsed" class="ti ti-chevron-left text-sm"></i>
                    <i v-show="sidebarCollapsed" class="ti ti-chevron-right text-sm"></i>
                </button>
            </div>

            <nav class="flex-1 space-y-0.5 p-2 overflow-y-auto">
                <Link v-for="item in menuItems" :key="item.id"
                      :href="route(item.route)"
                      replace
                      class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 transition-all duration-150"
                      :class="[isActive(item) ? 'bg-gradient-to-r from-pink-500 to-pink-600 text-white shadow-xs' : 'text-teal-100 hover:bg-teal-800/40 hover:text-white', sidebarCollapsed ? 'justify-center' : '']"
                      :title="item.label">
                    <i :class="`ti ${item.icon} text-sm flex-shrink-0`"></i>
                    <span v-show="!sidebarCollapsed" class="text-xs font-bold truncate">{{ item.label }}</span>
                </Link>
            </nav>

            <div class="border-t border-white/5 p-2 text-center text-[10px] text-teal-400/80 font-bold tracking-wide flex-shrink-0">
                <span v-show="!sidebarCollapsed">VERSION 2.0.0</span>
                <span v-show="sidebarCollapsed">V2.0</span>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex flex-col h-full min-w-0 flex-1 overflow-hidden">
            <!-- Topbar -->
            <header class="sticky top-0 z-20 flex h-16 w-full items-center justify-between border-b border-gray-200 bg-white/90 px-6 backdrop-blur-md flex-shrink-0">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <button @click="mobileOpen = true"
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-teal-900 text-white shadow-md md:hidden border border-teal-800 hover:bg-teal-800 transition">
                        <i class="ti ti-menu-2 text-lg"></i>
                    </button>
                    <div class="flex flex-col min-w-0">
                        <h1 class="text-base font-bold text-teal-950 truncate leading-tight">
                            {{ page.props?.title || 'Admin Dashboard' }}
                        </h1>
                        <p class="hidden text-[11px] font-medium text-gray-400 sm:block tracking-wide mt-0.5 truncate">
                            CHTM-RRS Hotel Management System
                        </p>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <div class="flex-1 overflow-x-hidden overflow-y-auto w-full min-w-0 bg-gray-50/50">
                <main class="p-4 sm:p-6 w-full max-w-full min-w-0">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>