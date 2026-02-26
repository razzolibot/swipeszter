<template>
  <span class="hashtag-text">
    <template v-for="(part, i) in parts" :key="i">
      <router-link
        v-if="part.type === 'hashtag'"
        :to="`/hashtag/${part.slug}`"
        class="hashtag-link"
        @click.stop
      >#{{ part.text }}</router-link>
      <span v-else>{{ part.text }}</span>
    </template>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  text: { type: String, default: '' },
})

const parts = computed(() => {
  if (!props.text) return []

  const result = []
  // #hashtag regex — magyar karakterekkel is
  const regex = /#([a-zA-ZÀ-öø-ÿ0-9_]+)/gu
  let lastIndex = 0
  let match

  while ((match = regex.exec(props.text)) !== null) {
    // Szöveg a hashtag előtt
    if (match.index > lastIndex) {
      result.push({ type: 'text', text: props.text.slice(lastIndex, match.index) })
    }
    // Hashtag
    result.push({
      type: 'hashtag',
      text: match[1],
      slug: match[1].toLowerCase(),
    })
    lastIndex = regex.lastIndex
  }

  // Maradék szöveg
  if (lastIndex < props.text.length) {
    result.push({ type: 'text', text: props.text.slice(lastIndex) })
  }

  return result
})
</script>

<style scoped>
.hashtag-text {
  line-height: 1.5;
}

.hashtag-link {
  color: #fe2c55;
  text-decoration: none;
  font-weight: 600;
}

.hashtag-link:hover {
  text-decoration: underline;
}
</style>
