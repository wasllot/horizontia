<script setup lang="ts">
defineProps<{ value?: any }>()

const checked = defineModel<any[] | boolean>("modelValue")
</script>

<template>
    <input v-model="checked" :value="value" type="checkbox" class="text-[#754FFE] border-[#EAEAEA] rounded-[4px] hover:text-[#754FFE] focus:text-[#754FFE]" />
</template>
