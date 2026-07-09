<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()
const props = page.props
const user = ref(props.auth?.user || {})

const form = ref({
    fname: user.value.fname || '',
    lname: user.value.lname || '',
    email: user.value.email || '',
})

function updateProfile() {
    router.patch('/profile', form.value, { preserveState: true })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-2xl">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Profile</h1>
                <p class="text-xs text-gray-500">Manage your account information and preferences.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
                <h3 class="font-bold text-gray-800">Account Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">First Name</label>
                        <input v-model="form.fname" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Last Name</label>
                        <input v-model="form.lname" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none" />
                    </div>
                </div>
                <button @click="updateProfile" class="px-4 py-2 bg-pink-500 text-white rounded-lg text-xs font-bold hover:bg-pink-600 transition">Save Changes</button>
            </div>
        </div>
    </AppLayout>
</template>