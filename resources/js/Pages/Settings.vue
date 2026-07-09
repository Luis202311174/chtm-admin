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
            <div>
                <h1 class="text-xl font-bold text-gray-900">System Settings</h1>
                <p class="text-xs text-gray-500">Configure application preferences and security settings.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-1 shadow-xs inline-flex">
                <button @click="switchTab('notifications')" class="px-4 py-2 text-xs font-bold rounded-lg transition" :class="activeTab === 'notifications' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Notifications</button>
                <button @click="switchTab('appearance')" class="px-4 py-2 text-xs font-bold rounded-lg transition" :class="activeTab === 'appearance' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Appearance</button>
                <button @click="switchTab('admin')" class="px-4 py-2 text-xs font-bold rounded-lg transition" :class="activeTab === 'admin' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Admin</button>
            </div>

            <div v-if="activeTab === 'notifications'" class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
                <h3 class="font-bold text-gray-800">Notification Preferences</h3>
                <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" v-model="notifData.checkIns" class="rounded" /> Check-Ins</label>
                <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" v-model="notifData.checkOuts" class="rounded" /> Check-Outs</label>
                <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" v-model="notifData.reservations" class="rounded" /> Reservations</label>
                <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" v-model="notifData.ratings" class="rounded" /> Ratings</label>
                <button @click="saveNotifications" class="mt-2 px-4 py-2 bg-pink-500 text-white rounded-lg text-xs font-bold hover:bg-pink-600">Save</button>
            </div>

            <div v-if="activeTab === 'appearance'" class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
                <h3 class="font-bold text-gray-800">Appearance</h3>
                <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" v-model="darkMode" class="rounded" /> Dark Mode</label>
                <button @click="saveAppearance" class="mt-2 px-4 py-2 bg-pink-500 text-white rounded-lg text-xs font-bold hover:bg-pink-600">Save</button>
            </div>

            <div v-if="activeTab === 'admin'" class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
                <h3 class="font-bold text-gray-800">Security Settings</h3>
                <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" v-model="twoFactor" class="rounded" /> Two-Factor Authentication</label>
                <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" v-model="loginAlert" class="rounded" /> Login Alerts</label>
                <button @click="saveAdmin" class="mt-2 px-4 py-2 bg-pink-500 text-white rounded-lg text-xs font-bold hover:bg-pink-600">Save</button>
            </div>
        </div>
    </AppLayout>
</template>