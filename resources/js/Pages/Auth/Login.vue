<script setup>
import { ref, reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()
const status = ref(page.props?.status ?? null)
const errors = ref(page.props?.errors ?? {})
const processing = ref(false)

const form = reactive({
    email: '',
    password: '',
})

function submit() {
    processing.value = true
    errors.value = {}
    
    router.post('/login', form, {
        onSuccess: () => {
            processing.value = false
        },
        onError: (errs) => {
            errors.value = errs
            processing.value = false
        },
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-900 text-white">
                        <i class="ti ti-building-skyscraper text-2xl"></i>
                    </div>
                </div>
                <h2 class="text-heading-1">Sign in to CHTM RRS</h2>
                <p class="text-body text-gray-500 mt-2">Hotel Management System</p>
            </div>
            
            <!-- Status Message (e.g., after password reset) -->
            <div v-if="status" class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ status }}
            </div>
            
            <!-- Error Message -->
            <div v-if="errors?.email" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ errors.email }}
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label class="form-label" for="email">Email Address</label>
                        <input 
                            id="email" 
                            v-model="form.email" 
                            name="email" 
                            type="email" 
                            required 
                            autocomplete="username"
                            class="form-input"
                            placeholder="Enter your email"
                        />
                    </div>
                    <div>
                        <label class="form-label" for="password">Password</label>
                        <input 
                            id="password" 
                            v-model="form.password" 
                            name="password" 
                            type="password" 
                            required 
                            autocomplete="current-password"
                            class="form-input"
                            placeholder="Enter your password"
                        />
                    </div>
                </div>
                
                <div>
                    <button 
                        type="submit"
                        :disabled="processing"
                        class="btn btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i v-if="processing" class="ti ti-loader animate-spin text-base"></i>
                        <i v-else class="ti ti-login text-base"></i>
                        {{ processing ? 'Signing in...' : 'Sign in' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>