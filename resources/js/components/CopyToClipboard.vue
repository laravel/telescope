<script type="text/ecmascript-6">
    export default {
        props: ['data'],
        data() {
            return {
                copying: false
            }
        },
        methods: {
            copy2Clipboard() {
                const self = this,
                    el = document.createElement('textarea');
                this.copying = true;
                el.value = JSON.stringify(this.data);
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                setTimeout(function() {
                    self.copying = false;
                }, 2000)
            }
        },
        computed: {
            anchorText() {
                return this.copying
                    ? 'Copied...'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path d="M19 21H8V7h11m0-2H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m-3-4H4a2 2 0 0 0-2 2v14h2V3h12V1z"/></svg>'
            }
        }
    }
</script>

<template>
    <a href="#"
       class="copy-to-clipboard"
       title="Copy to clipboard"
       @click.prevent="copy2Clipboard"
       v-html="anchorText">
    </a>
</template>
