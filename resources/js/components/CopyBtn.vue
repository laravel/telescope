<script type="text/ecmascript-6">
import $ from 'jquery';

export default {
    props: ['text'],

    mounted() {
        $('[data-toggle="tooltip"]').tooltip({})
    },

    methods: {
        copyTextToClipboard() {
            if (!navigator.clipboard) {
                this.fallbackCopyTextToClipboard(this.text)
                return;
            }
            navigator.clipboard.writeText(this.text).then(function () {
                this.updateTooltipText()
            }, function (err) {
                alert('Unable to copying sql.')
            });
        },
        fallbackCopyTextToClipboard() {
            let textArea = document.createElement("textarea");
            textArea.value = this.text;
            // Avoid scrolling to bottom
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                this.updateTooltipText()
            } catch (err) {
                alert('Unable to copying sql.')
            }

            document.body.removeChild(textArea);
        },
        updateTooltipText() {
            $('.copy-btn').tooltip('dispose').attr('data-original-title', 'Copied!').tooltip('show').attr('data-original-title', 'Copy to clipboard');
        }
    }
}
</script>

<template>
    <button
        class="d-inline btn btn-outline-primary btn-sm copy-btn"
        @click="copyTextToClipboard()"
        data-toggle="tooltip" data-placement="top" data-original-title="Copy to clipboard"
    >
        Copy
    </button>
</template>
