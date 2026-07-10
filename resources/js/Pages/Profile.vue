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
            <div class="page-header">
                <h1 class="text-heading-1">Profile</h1>
                <p class="text-body text-gray-500">Manage your account information and preferences.</p>
            </div>

            <div class="card">
                <div class="p-6 space-y-4">
                    <h3 class="text-heading-3">Account Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">First Name</label>
                            <input v-model="form.fname" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Last Name</label>
                            <input v-model="form.lname" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Email</label>
                            <input v-model="form.email" type="email" class="form-input" />
                        </div>
                    </div>
                    <div class="pt-4">
                        <button @click="updateProfile" class="btn btn-primary">
                            <i class="ti ti-device-floppy text-xs"></i>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>