<script setup lang="ts">
import { ref, computed } from "vue"
import { UseClipboard } from "@vueuse/components"
import TranslationItem from "./translation-item.vue"
import { SourceTranslation, Translation } from "../../../scripts/types"
import useConfirmationDialog from "../../../scripts/composables/use-confirmation-dialog"

const props = defineProps<{
    translations?: Record<string, Translation>
    sourceTranslation?: Record<string, SourceTranslation>
}>()

const searchQuery = ref("")
const selectedIds = ref<number[]>([])

const { loading, showDialog, openDialog, performAction, closeDialog } = useConfirmationDialog()

const deleteTranslations = async (id: number) => {
    await performAction(() =>
        router.post(
            route("ltu.translation.delete_multiple"),
            { selected_ids: selectedIds.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = []
                },
            },
        ),
    )
}

const filteredTranslations = computed(() => {
    return Object.keys(props.translations).map((key) => {
        return props.translations[key];
    }).filter((translation: Translation | undefined) => {
        return (
            translation &&
            (translation.language.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                translation.language.code.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    });
});


function toggleSelection() {
    if (selectedIds.value.length === props.translations.length) {
        selectedIds.value = []

    } else {
        selectedIds.value = props.translations.map((language: Translation) => language.id)
    }
}

const isAllSelected = computed(() => selectedIds.value.length === Object.keys(props.translations).length)
</script>

<template>
    <Head title="Translations" />

    <LayoutDashboard>
        <div class="tb-dashboard_box min-h-[calc(100vh-116px)] m-[0_20px_0_10px] tb-translationswrap">
            <div class="max-w-[820px] mx-auto">
                <div v-if="sourceTranslation" class="w-full">
                    <div class="w-auto mt-[20px] [&_input]:border-[#F7F7F8] [&:hover_input]:border-[#EAEAEA] [&:hover_input]:!lx-shadow-[0px_1px_2px_0px_rgba(16,24,40,0.05)]">
                        <InputText v-model="searchQuery" class="rounded-[10px] bg-[#F7F7F8] h-[40px] transition-all hover:bg-white focus:!border-[#CEBEED] focus:!bg-white" placeholder="Search languages by name or code" />
                    </div>
                </div>
    
                <div v-if="sourceTranslation" class="mt-4 overflow-hidden bg-white rounded-lg p-[20px_20px_0] border shadow-[0px_1px_3px_0px_rgba(16,24,40,0.1),0px_1px_2px_0px_rgba(16,24,40,0.06)] rounded-[20px] border-solid border-[#EAEAEA]">
                    <div class="relative z-10 flex w-full bg-[#F7F7F8] rounded-[10px]">
                        <div class="flex items-center justify-center w-14 shrink-0">
                            <div class="flex items-center shrink-0">
                                <InputCheckbox :disabled="!translations.length" :checked="isAllSelected" @input="toggleSelection" />
                            </div>
                        </div>
    
                        <div class="items-center p-[12px] text-[14px] leading-[20px] text-[#585858] font-semibold flex-1 hidden sm:flex">Language name</div>
    
                        <div class="items-center flex-1 hidden p-[12px] text-[14px] text-[#585858] leading-[20px] font-semibold md:flex md:max-w-72 lg:max-w-80 xl:max-w-96">Translation progress</div>
    
                        <div class="w-full sm:w-14">
                            <Link v-tooltip="'Add Language'" :href="route('ltu.translation.create')" class="relative inline-flex items-center justify-center w-full text-sm font-medium tracking-wide text-[#585858] transition-colors duration-150 ease-out outline-none cursor-pointer h-[44px] select-none hover:text-[#585858]">
                                <div class="flex items-center justify-center visible leading-none size-full">
                                    <span class="mx-auto whitespace-nowrap sm:hidden">Add Language</span>
    
                                    <IconPlus class="hidden fill-current size-5 sm:flex" />
                                </div>
                            </Link>
                        </div>
    
                        <div class="flex h-full">
                            <div class="flex w-full max-w-full">
                                <button
                                    v-tooltip="selectedIds.length ? 'Delete selected' : 'Select languages to delete'"
                                    type="button"
                                    class="relative inline-flex items-center justify-center text-sm font-medium tracking-wide text-[#585858] no-underline uppercase transition-colors rounded-[0_10px_10px_0] duration-150 ease-out outline-none select-none size-[44px]"
                                    :disabled="!selectedIds.length"
                                    :class="{
                                        'cursor-not-allowed': !selectedIds.length,
                                        'cursor-pointer': selectedIds.length,
                                        'hover:bg-red-50 hover:text-red-600': selectedIds.length,
                                        '': !selectedIds.length,
                                    }"
                                    @click="openDialog">
                                    <IconTrash class="size-5" />
                                </button>
                            </div>
                        </div>
    
                        <ConfirmationDialog size="sm" :show="showDialog">
                            <div class="flex flex-col p-6">
                                <span class="text-xl font-medium text-gray-700">Are you sure?</span>
    
                                <span class="mt-2 text-sm text-gray-500"> This action cannot be undone, This will permanently delete the selected languages and all of their translations. </span>
    
                                <div class="flex gap-4 mt-4">
                                    <BaseButton variant="secondary" type="button" size="lg" full-width @click="closeDialog"> Cancel </BaseButton>
    
                                    <BaseButton variant="danger" type="button" size="lg" :is-loading="loading" full-width @click="deleteTranslations"> Delete </BaseButton>
                                </div>
                            </div>
                        </ConfirmationDialog>
                    </div>
    
                    <div class="">
                        <div class="flex flex-row no-underline transition-colors duration-100 border-b">
                            <div class="flex flex-1 no-underline">
                                <div class="flex max-w-full">
                                    <div class="flex items-center justify-center mr-0 w-14 bg-gray-50">
                                        <div class="flex items-center opacity-50 shrink-0">
                                            <InputCheckbox disabled />
                                        </div>
                                    </div>
                                </div>
    
                                <Link :href="route('ltu.source_translation')" class="flex w-full">
                                    <div class="flex justify-between flex-1 p-[7.5px_14px] truncate gap-x-4">
                                        <div class="flex items-center w-full font-medium truncate">
                                            <Flag :country-code="sourceTranslation.language.code" class="mr-2" />
    
                                            <span class="max-w-full mr-2 text-[14px] leading-[20px] text-[#585858] font-semibold truncate">
                                                {{ sourceTranslation.language.name }}
                                            </span>
    
                                            <div class="inline-block">
                                                <span class="flex h-5 items-center rounded-md border px-1.5 text-xs font-normal leading-none text-gray-600">
                                                    {{ sourceTranslation.language.code }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
    
                                    <div class="flex-wrap items-center content-center flex-1 hidden gap-1 p-[7.5px_14px] md:flex md:max-w-72 lg:max-w-80 xl:max-w-96">
                                        <span class="mr-1 text-[14px] leading-[20px] text-[#585858] shrink-0">{{ sourceTranslation.phrases_count }} source keys</span>
                                    </div>
                                </Link>
                            </div>
    
                            <Link v-tooltip="'Manage Keys'" :href="route('ltu.source_translation')" class="hidden w-full group sm:flex sm:w-14">
                                <div class="relative inline-flex items-center justify-center w-full text-sm font-medium tracking-wide text-gray-700 transition-colors duration-150 ease-out outline-none cursor-pointer select-none h-[38px] ">
                                    <IconCog class="hidden text-gray-400 fill-current size-5 sm:flex" />
                                </div>
                            </Link>
    
                            <div v-tooltip="'Source language cannot be deleted!'" class="flex h-full">
                                <div class="flex w-full max-w-full">
                                    <div class="relative inline-flex items-center justify-center text-sm font-medium tracking-wide text-gray-400 no-underline uppercase transition-colors duration-150 ease-out outline-none cursor-not-allowed select-none w-[44px] h-[38px]">
                                        <IconTrash class="size-5" />
                                    </div>
                                </div>
                            </div>
                        </div>
    
                        <template v-if="filteredTranslations.length">
                            <TranslationItem
                                v-for="translation in filteredTranslations"
                                :key="translation.language.code"
                                :translation="translation"
                                :selected-ids="selectedIds"
                            />
                        </template>
    
                        <Link v-if="filteredTranslations.length" :href="route('ltu.translation.create')" class="flex items-center justify-between p-[10px] text-[#585858] hover:text-[#585858] text-[14px] leading-5 transition-colors duration-100 cursor-pointer">
                            <div class="text-sm font-medium tracking-wide uppercase">Add new language</div>
    
                            <IconPlus class="size-6" />
                        </Link>
    
                        <EmptyState v-else class="py-32 bg-gray-50" title="There are no languages to translate your project to." description="Add the first one and start translating.">
                            <template #icon>
                                <IconEmptyTranslations class="w-20 text-gray-400" />
                            </template>
    
                            <Link :href="route('ltu.translation.create')" class="mt-4 btn btn-primary btn-md">
                                <div class="flex items-center visible leading-none">
                                    <IconPlus class="mr-1 size-5 fill-white" />
    
                                    <span class="whitespace-nowrap">Add languages</span>
                                </div>
                            </Link>
                        </EmptyState>
                    </div>
                </div>
                <EmptyState v-else class="mt-4 min-h-[calc(100vh-11rem)] rounded-md border bg-white py-12 shadow" title="Source language has not been imported yet." description="To import the source translation, run the following command in your terminal or hit import button.">
                    <template #icon>
                        <IconEmptyTranslations class="w-20 text-gray-400" />
                    </template>
    
                    <code class="flex items-center mt-4 border rounded-md">
                        <span class="px-2 py-1 text-sm font-medium text-gray-500">php artisan translation:import</span>
    
                        <UseClipboard v-slot="{ copy, copied }" source="php artisan translation:import">
                            <button v-tooltip="copied ? 'Copied' : 'Copy'" type="button" class="h-full p-2 text-gray-400 border-l hover:bg-blue-50 hover:text-blue-500" @click="copy()">
                                <IconClipboard class="size-4" />
                            </button>
                        </UseClipboard>
                    </code>
                </EmptyState>
            </div>

        </div>
    </LayoutDashboard>
</template>
