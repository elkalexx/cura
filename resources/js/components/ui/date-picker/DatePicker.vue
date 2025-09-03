<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { cn } from '@/lib/utils'
import { CalendarDate, getLocalTimeZone, today } from '@internationalized/date'
import { format } from 'date-fns'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { computed } from 'vue'

const props = defineProps<{
    modelValue?: Date,
    allowPast?: boolean
}>()

const emits = defineEmits<{
    (e: 'update:modelValue', value?: Date): void
}>()

const minValue = computed(() => {
    if (props.allowPast) {
        return undefined
    }

    return today(getLocalTimeZone())
})

const value = computed({
    get: () => (props.modelValue ? new CalendarDate(props.modelValue.getFullYear(), props.modelValue.getMonth() + 1, props.modelValue.getDate()) : undefined),
    set: (val) => {
        if (!val) {
            emits('update:modelValue', undefined)
            return
        }
        emits('update:modelValue', val.toDate(getLocalTimeZone()))
    },
})
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="
          cn(
            'w-full justify-start text-left font-normal',
            !modelValue && 'text-muted-foreground',
          )
        "
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ modelValue ? format(modelValue, 'PPP') : 'Pick a date' }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <Calendar v-model="value" initial-focus :week-starts-on="1" :min-value="minValue"  />
        </PopoverContent>
    </Popover>
</template>
