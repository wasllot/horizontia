<script setup lang="ts">
import { Bars3Icon, XMarkIcon, ArrowRightEndOnRectangleIcon } from "@heroicons/vue/24/outline"
import { Popover, PopoverButton, PopoverOverlay, PopoverPanel, TransitionChild, TransitionRoot } from "@headlessui/vue"

const user = useAuth()

const navigation = [
    { name: "Translations", href: route("ltu.translation.index"), current: route().current("ltu.translation*") || route().current("ltu.source_translation*") || route().current("ltu.phrases*") },
]

const loading = ref(false)

const form = useForm({
    
})

const importTranslations = () => {
    loading.value = true
    form.get(route('ltu.translation.import'), {
        preserveScroll: true,
        onSuccess: () => {
            loading.value = false
        },
        onError: () => {
            loading.value = false
        }
    })
}
</script>

<template>
    <div class="px-2 mx-auto max-w-7xl sm:px-4 lg:px-8">
        <Popover v-slot="{ open }" class="flex justify-between h-16">
            <div class="flex px-2 lg:px-0">
                <div class="flex items-center shrink-0">
                    <Link :href="route('ltu.translation.index')">
                        <Logo class="w-auto h-8 text-black" />
                    </Link>
                </div>

                <nav aria-label="Global" class="hidden lg:ml-6 lg:flex lg:items-center lg:space-x-4">
                    <Link v-for="item in navigation" :key="item.name" :href="item.href" class="p-[10px_16px] text-[14px] leading-[20px] font-medium" :class="[item.current ? 'bg-[#051237] text-white rounded-[10px] min-h-[40px]' : 'text-white hover:bg-blue-700 hover:text-white']" :aria-current="item.current ? 'page' : undefined">
                        {{ item.name }}
                    </Link>
                </nav>
            </div>

            <div class="flex items-center lg:hidden">
                <PopoverButton class="relative inline-flex items-center justify-center p-2 text-white rounded-md focus:outline-none">
                    <span class="absolute -inset-0.5" />

                    <span class="sr-only">Open main menu</span>

                    <Bars3Icon class="block size-6" aria-hidden="true" />
                </PopoverButton>
            </div>

            <TransitionRoot as="template" :show="open">
                <div class="lg:hidden">
                    <TransitionChild as="template" enter="duration-150 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-150 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                        <PopoverOverlay class="fixed inset-0 z-20 bg-black/25" aria-hidden="true" />
                    </TransitionChild>

                    <TransitionChild as="template" enter="duration-150 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-150 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                        <PopoverPanel focus class="absolute top-0 right-0 z-30 w-full p-2 transition origin-top max-w-none">
                            <div class="bg-white divide-y divide-gray-200 rounded-lg shadow-lg ring-1 ring-black/5">
                                <div class="pt-3 pb-2">
                                    <div class="flex items-center justify-between px-4">
                                        <Link :href="route('ltu.translation.index')" tabindex="-1" class="flex items-center gap-3">
                                            <Logo class="w-auto h-8" />

                                            <h1 class="mt-1 text-xl font-medium text-gray-600">Translations <span class="font-bold text-blue-600">UI</span></h1>
                                        </Link>

                                        <div class="-mr-2">
                                            <PopoverButton class="relative inline-flex items-center justify-center p-2 text-gray-400 bg-white rounded-md hover:bg-gray-100 hover:text-gray-500 focus:outline-none">
                                                <span class="absolute -inset-0.5" />

                                                <span class="sr-only">Close menu</span>

                                                <XMarkIcon class="size-6" aria-hidden="true" />
                                            </PopoverButton>
                                        </div>
                                    </div>

                                    <div class="px-2 mt-6 space-y-1">
                                        <Link v-for="item in navigation" :key="item.name" :href="item.href" class="block px-3 py-2 text-base font-medium text-gray-900 rounded-md hover:bg-gray-100 hover:text-gray-800">
                                            {{ item.name }}
                                        </Link>
                                    </div>
                                </div>

                                <div class="py-4">
                                    <div class="flex items-center px-5">
                                        <div>
                                            <div class="text-base font-medium text-gray-800">{{ user.name }}</div>

                                            <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </PopoverPanel>
                    </TransitionChild>
                </div>
            </TransitionRoot>

            
            <div class="hidden gap-4 lg:ml-4 lg:flex lg:items-center">
                <!-- import button -->
                <div class="flex">
                    <a href="javascript:void(0);" :class="['p-[10px_16px] text-[14px] leading-[20px] font-medium flex text-[#fff] items-center gap-[6px] rounded-[10px] bg-[#47CD89] hover:text-[#fff] disabled:opacity-50 disabled:cursor-not-allowed', { 'tb-btn_disable': loading }]" :disabled="loading" @click="importTranslations">
                        <svg fill="#fff" class="size-4 icon line" viewBox="0 0 24 24" id="import" xmlns="http://www.w3.org/2000/svg"><polyline id="primary" points="13 9 13 13 9 13" style="fill: none; stroke: #fff; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></polyline><path id="primary-2" data-name="primary" d="M13.89,5H20a1,1,0,0,1,1,1V20a1,1,0,0,1-1,1H6a1,1,0,0,1-1-1V13.89" style="fill: none; stroke: #fff; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></path><line id="primary-3" data-name="primary" x1="3" y1="3" x2="13" y2="13" style="fill: none; stroke: #fff; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5;"></line></svg>
                        <span>Import</span>
                    </a>
                </div>
            
                <div class="flex">
                    <Link :href="route('ltu.translation.publish')" class="p-[10px_16px] text-[14px] leading-[20px] font-medium flex text-[#fff] items-center gap-[6px] rounded-[10px] bg-[#47CD89] hover:text-[#fff]">
                        <IconPublish class="size-4" />
                        <span>Publish</span>
                    </Link>
                </div>
            </div>
        </Popover>
    </div>
</template>
