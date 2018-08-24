<script type="text/ecmascript-6">
    import _ from 'lodash';
    import axios from 'axios';
    import $ from 'jquery';

    export default {
        components: {},


        /**
         * The component's data.
         */
        data() {
            return {
                entry: null,
                ready: false,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Mail - Telescope";

            axios.get('/telescope/telescope-api/mail/' + this.$route.params.id).then(response => {
                this.entry = response.data.entry;

                this.ready = true;
            }).catch(error => {
                this.ready = true;
            })
        },


        methods: {
            /**
             * Format the given list of addresses.
             */
            formatAddresses(addresses){
                return _.chain(addresses).map((name, email) => {
                    return (name ? "<" + name + "> " : '') + email;
                }).join(', ').value()
            }
        }
    }
</script>

<template>
    <loader :loading="!ready">
        <div v-if="!entry">No entry found.</div>

        <div v-else>
            <table class="table table-sm">
                <tr>
                    <td class="font-weight-bold pl-0">Time</td>
                    <td>{{entry.content.time}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">From</td>
                    <td>{{formatAddresses(entry.content.from)}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">To</td>
                    <td>{{formatAddresses(entry.content.to)}}</td>
                </tr>

                <tr v-if="entry.content.replyTo">
                    <td class="font-weight-bold pl-0">Reply-To</td>
                    <td>{{formatAddresses(entry.content.replyTo)}}</td>
                </tr>

                <tr v-if="entry.content.cc">
                    <td class="font-weight-bold pl-0">CC</td>
                    <td>{{formatAddresses(entry.content.cc)}}</td>
                </tr>

                <tr v-if="entry.content.bcc">
                    <td class="font-weight-bold pl-0">BCC</td>
                    <td>{{formatAddresses(entry.content.bcc)}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Subject</td>
                    <td>{{entry.content.subject}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Download</td>
                    <td>
                        <a :href="'/telescope/telescope-api/mail/'+entry.id+'/download'">Download .eml file</a>
                    </td>
                </tr>
            </table>

            <iframe :src="'/telescope/telescope-api/mail/'+entry.id+'/preview'" width="100%" height="400"></iframe>
        </div>
    </loader>
</template>

<style scoped>
    iframe {
        border: solid 1px #ccc;
    }
</style>