<script setup lang="ts">
import vSelect from "vue-select"
import { ref, Ref } from "vue"
import { Language } from "../../../../scripts/types"

defineProps<{
    languages: Language
}>()

const { close } = useModal()

const selected: Ref<Language[]> = ref([])

const form = useForm({
    languages: [],
})

const submit = () => {
    form.post(route("ltu.translation.store"), {
        preserveScroll: true,
        onSuccess: () => {
            close()
        },
    })
}
</script>

<template>
    <Head title="Add Languages" />

    <Dialog size="lg">
        <div class="flex items-start justify-between gap-4 px-6 py-4 border-b">
            <div class="flex items-center justify-center size-12 shrink-0">
                <IconTranslation class="text-gray-500 size-10" />
            </div>

            <div class="w-full">
                <h3 class="text-base font-semibold leading-6 text-gray-600">Add Languages</h3>

                <p class="mt-1 text-sm text-gray-500">Select the languages you want to add to your project.</p>
            </div>

            <div class="flex items-center justify-center w-8 text-gray-400 cursor-pointer hover:text-gray-600" @click="close">
                <IconClose class="size-5" />
            </div>
        </div>

        <input type="text" class="absolute h-0 p-0 m-0 opacity-0" autofocus />

        <div class="mt-0 w-full p-[15px]" @click.stop>
            <vSelect v-model="form.languages" label="name" class="bg-white rounded-md" :options="languages" :reduce="(language: Language) => language.id" :selectable="(language: Language) => !form.languages.includes(language.id)" multiple>
                <template #search="{ attributes, events }">
                    <input class="vs__search" :required="!selected" v-bind="attributes" :placeholder="!form.languages.length ? 'Search languages...' : ''" v-on="events" />
                </template>

                <template #option="{ name, code }">
                    <div class="flex items-center justify-between w-full group">
                        <div class="flex items-center flex-1 gap-2">
                            <flag width="w-6" :country-code="code" />

                            <h3 class="font-medium">{{ name }}</h3>
                        </div>

                        <small class="shrink-0 rounded-md border px-1 py-0.5 text-xs text-gray-400 group-hover:text-white">{{ code }}</small>
                    </div>
                </template>

                <template #selected-option="{ name, code }">
                    <flag width="w-5" :country-code="code" />

                    <h3 class="text-sm font-medium">{{ name }}</h3>
                </template>
            </vSelect>
        </div>

        <div class="grid grid-cols-1 gap-6 px-6 py-4 border-t md:grid-cols-2">
            <BaseButton variant="secondary" type="button" size="lg" @click="close"> Close </BaseButton>

            <BaseButton variant="primary" type="button" size="lg" :disabled="!form.languages.length || form.processing" :is-loading="form.processing" @click="submit"> Add Languages </BaseButton>
        </div>
    </Dialog>
</template>
