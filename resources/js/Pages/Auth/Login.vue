<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Sign in to CHTM RRS</h2>
            </div>
            
            <!-- Status Message (e.g., after password reset) -->
            <div v-if="status" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ status }}
            </div>
            
            <!-- Error Message -->
            <div v-if="errors?.email" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ errors.email }}
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <input 
                            id="email" 
                            v-model="form.email" 
                            name="email" 
                            type="email" 
                            required 
                            autocomplete="username"
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 focus:z-10 sm:text-sm" 
                            placeholder="Email address"
                        />
                    </div>
                    <div>
                        <input 
                            id="password" 
                            v-model="form.password" 
                            name="password" 
                            type="password" 
                            required 
                            autocomplete="current-password"
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 focus:z-10 sm:text-sm" 
                            placeholder="Password"
                        />
                    </div>
                </div>
                <div>
                    <button 
                        type="submit"
                        :disabled="processing"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="processing" class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

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