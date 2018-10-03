<script type="text/ecmascript-6">
    export default {
        props: ['lines', 'highlightedLine'],
    }
</script>

<template>
    <pre class="code-bg px-4 mb-0 text-white">
        <p v-for="(line, number) in lines"
           class="mb-0"
           :class="{'highlight': number == highlightedLine}"><span class="mr-4">{{number}}</span> <span>{{line}}</span></p>
    </pre>
</template>

<style scoped>
    .highlight {
        background-color: #ff647a;
    }
</style>
