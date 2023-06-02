<script type="text/ecmascript-6">

export default {
    props: ['data'],

    data() {
        return {
            copying: false,
            canCopy: false
        }
    },

    created() {
        this.canCopy = !!navigator.clipboard;
    },

    methods: {
        async copy() {
            this.copying = true;
            await navigator.clipboard.writeText(this.data);
            setTimeout(() => this.copying = false, 2000);
        }
    },

    computed: {
        copyText() {
            return this.copying
                ? 'Copied...'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path d="M19 21H8V7h11m0-2H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m-3-4H4a2 2 0 0 0-2 2v14h2V3h12V1z"/></svg>'
        }
    }
}
</script>

<template>
    <div class="code-bg p-4 mb-0 text-white position-relative">
        <a href="#"
           class="copy-to-clipboard"
           title="Copy to clipboard"
           @click.prevent="copy"
           v-html="copyText">
        </a>
        <vue-json-pretty :data="data"></vue-json-pretty>
    </div>
</template>

