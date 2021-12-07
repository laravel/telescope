<script type="text/ecmascript-6">
    import hljs from 'highlight.js/lib/core';
    import php from 'highlight.js/lib/languages/php';

    hljs.registerLanguage('php', php);

    export default {
        props: ['lines', 'highlightedLine'],

        methods: {
            highlight(line, number){
                if (number == this.highlightedLine) {
                    return line;
                }

                return hljs.highlight(line, { language: 'php' }).value;
            }
        }
    }
</script>

<template>
    <pre class="code-bg px-4 mb-0 text-white">
        <p v-for="(line, number) in lines"
           :key="number"
           class="mb-0"
           :class="{'highlight': number == highlightedLine}"><span class="mr-4">{{number}}</span> <span v-html="highlight(line, number)" /></p>
    </pre>
</template>

<style scoped>
    .highlight {
        background-color: #ff647a;
    }
</style>
