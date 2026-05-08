<script setup lang="ts">
import { ref } from "vue"
import { Translation } from "../../../scripts/types"
import useConfirmationDialog from "../../../scripts/composables/use-confirmation-dialog"

const props = defineProps<{
    translation: Translation
    selectedIds: number[]
}>()

const { loading, showDialog, openDialog, performAction, closeDialog } = useConfirmationDialog()

const deleteTranslation = async (id: number) => {
    await performAction(() => router.delete(route("ltu.translation.delete", id)))
}

const selected = ref(props.selectedIds.includes(props.translation.id))

watch(
    () => props.selectedIds,
    (newSelectedIds) => {
        selected.value = newSelectedIds.includes(props.translation.id)
    },
)
</script>

<template>
    <div class="flex flex-row no-underline transition-colors duration-100 border-b gap-y-2 hover:bg-gray-50">
        <div class="relative flex flex-1 no-underline">
            <div class="flex max-w-full">
                <div class="flex items-center justify-center mr-0 w-14">
                    <div class="flex items-center shrink-0">
                        <InputCheckbox v-model="selected" :value="translation.id" />
                    </div>
                </div>
            </div>

            <Link :href="route('ltu.phrases.index', translation.id)" class="flex w-full">
                <div class="flex justify-between flex-1 p-[7.5px_14px] truncate gap-x-4">
                    <div class="flex items-center w-full font-medium truncate">
                        <Flag :country-code="translation.language.code" class="mr-2" />

                        <span class="max-w-full mr-2 text-[14px] leading-[20px] text-[#585858] font-semibold truncate">
                            {{ translation.language.name }}
                        </span>

                        <div class="inline-block">
                            <span class="flex h-5 items-center rounded-md border px-1.5 text-xs font-normal leading-none text-gray-600">
                                {{ translation.language.code }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex-wrap items-center content-center flex-1 hidden gap-1 p-[7.5px_14px] md:flex md:max-w-72 lg:max-w-80 xl:max-w-96">
                    <div v-tooltip="`${translation.progress}` + ' strings translated'" class="w-full py-2">
                        <div class="w-full overflow-hidden bg-gray-200 rounded-full translation-progress">
                            <div class="h-2 bg-green-600" :style="{ width: `${translation.progress}` }"></div>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <div class="hidden w-full sm:flex sm:w-14">
            <Link v-tooltip="'Translate'" :href="route('ltu.phrases.index', translation.id)" class="relative inline-flex items-center justify-center w-full text-sm font-medium tracking-wide text-gray-400 transition-colors duration-150 ease-out outline-none cursor-pointer select-none h-[44px] hover:bg-blue-50 hover:text-blue-500 focus:border-blue-50">
                <IconLanguage class="hidden size-5 sm:flex" />
            </Link>
        </div>

        <div class="flex h-full">
            <div v-tooltip="'Delete'" class="flex w-full max-w-full">
                <button type="button" class="relative inline-flex items-center justify-center text-sm font-medium tracking-wide text-gray-400 no-underline uppercase transition-colors duration-150 ease-out outline-none cursor-pointer select-none w-[44px] h-[44px] hover:bg-red-50 hover:text-red-600" @click="openDialog">
                    <IconTrash class="size-5" />
                </button>
            </div>

            <ConfirmationDialog size="sm" :show="showDialog">
                <div class="flex flex-col p-6">
                    <span class="text-xl font-medium text-gray-700">Are you sure?</span>

                    <span class="mt-2 text-sm text-gray-500">
                        This action cannot be undone, This will permanently delete the <span class="font-medium text-gray-900">{{ translation.language.name }}</span> language and all of its translations.
                    </span>

                    <div class="flex gap-4 mt-4">
                        <BaseButton variant="secondary" type="button" size="lg" full-width @click="closeDialog"> Cancel </BaseButton>

                        <BaseButton variant="danger" type="button" size="lg" :is-loading="loading" full-width @click="deleteTranslation(translation.id)"> Delete </BaseButton>
                    </div>
                </div>
            </ConfirmationDialog>
        </div>
    </div>
</template>
