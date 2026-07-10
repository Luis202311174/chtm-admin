<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()
const props = page.props
const activeTab = ref(props.activeTab || 'notifications')

const notifData = ref(props.notifications || { checkIns: true, checkOuts: true, reservations: true, ratings: true })
const darkMode = ref(props.appearance?.darkMode || false)
const twoFactor = ref(props.twoFactorEnabled ?? true)
const loginAlert = ref(props.loginAlertEnabled ?? true)

function switchTab(tab) {
    activeTab.value = tab
    router.get('/settings', { tab }, { preserveState: true, replace: true })
}

function saveNotifications() {
    router.post('/settings', { ...notifData.value, tab: 'notifications' }, { preserveState: true })
}
function saveAppearance() {
    router.post('/settings', { darkMode: darkMode.value, tab: 'appearance' }, { preserveState: true })
}
function saveAdmin() {
    router.post('/settings', { twoFactorEnabled: twoFactor.value, loginAlertEnabled: loginAlert.value, tab: 'admin' }, { preserveState: true })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-3xl">
            <div class="page-header">
                <h1 class="text-heading-1">System Settings</h1>
                <p class="text-body text-gray-500">Configure application preferences and security settings.</p>
            </div>

            <div class="tab-group">
                <button @click="switchTab('notifications')" 
                        class="tab-button" 
                        :class="activeTab === 'notifications' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-bell text-sm"></i>
                    Notifications
                </button>
                <button @click="switchTab('appearance')" 
                        class="tab-button" 
                        :class="activeTab === 'appearance' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-palette text-sm"></i>
                    Appearance
                </button>
                <button @click="switchTab('admin')" 
                        class="tab-button" 
                        :class="activeTab === 'admin' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-shield text-sm"></i>
                    Admin
                </button>
            </div>

            <div v-if="activeTab === 'notifications'" class="card">
                <div class="p-6 space-y-4">
                    <h3 class="text-heading-3">Notification Preferences</h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="notifData.checkIns" class="rounded w-4 h-4 text-teal-600 focus:ring-teal-500" />
                        <span class="text-body">Check-Ins</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="notifData.checkOuts" class="rounded w-4 h-4 text-teal-600 focus:ring-teal-500" />
                        <span class="text-body">Check-Outs</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="notifData.reservations" class="rounded w-4 h-4 text-teal-600 focus:ring-teal-500" />
                        <span class="text-body">Reservations</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="notifData.ratings" class="rounded w-4 h-4 text-teal-600 focus:ring-teal-500" />
                        <span class="text-body">Ratings</span>
                    </label>
                    <div class="pt-4">
                        <button @click="saveNotifications" class="btn btn-primary">
                            <i class="ti ti-device-floppy text-xs"></i>
                            Save Preferences
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'appearance'" class="card">
                <div class="p-6 space-y-4">
                    <h3 class="text-heading-3">Appearance</h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="darkMode" class="rounded w-4 h-4 text-teal-600 focus:ring-teal-500" />
                        <span class="text-body">Dark Mode</span>
                    </label>
                    <div class="pt-4">
                        <button @click="saveAppearance" class="btn btn-primary">
                            <i class="ti ti-device-floppy text-xs"></i>
                            Save Appearance
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'admin'" class="card">
                <div class="p-6 space-y-4">
                    <h3 class="text-heading-3">Security Settings</h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="twoFactor" class="rounded w-4 h-4 text-teal-600 focus:ring-teal-500" />
                        <span class="text-body">Two-Factor Authentication</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="loginAlert" class="rounded w-4 h-4 text-teal-600 focus:ring-teal-500" />
                        <span class="text-body">Login Alerts</span>
                    </label>
                    <div class="pt-4">
                        <button @click="saveAdmin" class="btn btn-primary">
                            <i class="ti ti-device-floppy text-xs"></i>
                            Save Security Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>