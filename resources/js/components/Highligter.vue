<script type="text/ecmascript-6">
import hljs from 'highlight.js/lib/core';
import sql from 'highlight.js/lib/languages/sql';
import pgsql from 'highlight.js/lib/languages/pgsql'

hljs.registerLanguage('sql', sql)
hljs.registerLanguage('pgsql', pgsql)

function hasValueOrEmptyAttribute(value) {
    return Boolean(value || value === "");
}

function escapeHTML(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

export default {
    props: ["language", "code", "autodetect"],
    data: function() {
        return {
            detectedLanguage: "",
            unknownLanguage: false
        };
    },
    computed: {
        className() {
            if (this.unknownLanguage) return "";

            return "hljs " + this.detectedLanguage;
        },
    
        highlighted() {
            if (!this.autoDetect && !hljs.getLanguage(this.language)) {
                console.warn(`The language "${this.language}" you specified could not be found.`);    
                this.unknownLanguage = true;

                return escapeHTML(this.code);
            }

            let result = {};
    
            if (this.autoDetect) {
                result = hljs.highlightAuto(this.code);
                this.detectedLanguage = result.language;
            } else {
                result = hljs.highlight(this.code, { language: this.language, ignoreIllegals: this.ignoreIllegals });
                this.detectedLanguage = this.language;
            }
            return result.value;
        },

        autoDetect() {
            return ! this.language || hasValueOrEmptyAttribute(this.autodetect);
        },

        ignoreIllegals() {
            return true;
        }
    },

    template: `<pre><code :class="className" v-html="highlighted"></code></pre>`
};
</script>
