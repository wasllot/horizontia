<script setup lang="ts">
import { Modal } from "momentum-modal"
import Navbar from "./partials/navbar.vue"
import { ChevronRightIcon } from "@heroicons/vue/20/solid"

defineProps<{
    breadcrumbs?: {
        label: string
        link: string
    }[]
}>()
</script>

<template>
    <div class="min-h-full">
        <header class="divide-y bg-[transparent]">
            <Navbar />

            <div v-if="breadcrumbs" class="py-6 overflow-y-auto bg-white">
                <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 xl:flex xl:items-center xl:justify-between">
                    <div class="flex-1 min-w-0">
                        <nav v-if="breadcrumbs" class="flex" aria-label="Breadcrumb">
                            <ol role="list" class="flex items-center space-x-4">
                                <li>
                                    <Link :href="route('ltu.translation.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700"> Dashboard </Link>
                                </li>

                                <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center">
                                    <ChevronRightIcon class="text-gray-400 size-5 shrink-0" aria-hidden="true" />

                                    <Link v-if="crumb.link" :href="crumb.link" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                                        {{ crumb.label }}
                                    </Link>

                                    <span v-else class="ml-4 text-sm font-medium text-gray-500 whitespace-nowrap hover:text-gray-700">
                                        {{ crumb.label }}
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <Modal />
    </div>
</template>
