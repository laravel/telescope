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
            document.title = "Queue - Telescope";

            axios.get('/telescope/telescope-api/queue/' + this.$route.params.id).then(response => {
                this.entry = response.data.entry;

                this.ready = true;
            }).catch(error => {
                this.ready = true;
            })
        },
    }
</script>

<template>
    <loader :loading="!ready">
        <div v-if="!entry">No entry found.</div>

        <div v-else>
            <table class="table table-sm">
                <tr>
                    <td class="font-weight-bold pl-0">Status</td>
                    <td>{{entry.content.status}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Job</td>
                    <td>{{entry.content.name}}</td>
                </tr>

                <tr v-if="entry.content.tries">
                    <td class="font-weight-bold pl-0">Tries</td>
                    <td>{{entry.content.tries}}</td>
                </tr>

                <tr v-if="entry.content.timeout">
                    <td class="font-weight-bold pl-0">Timeout</td>
                    <td>{{entry.content.timeout}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Queue</td>
                    <td>{{entry.content.queue}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Connection</td>
                    <td>{{entry.content.connection}}</td>
                </tr>

                <tr v-if="entry.content.status == 'failed'">
                    <td class="font-weight-bold pl-0">Exception</td>
                    <td>{{entry.content.exception.message}}</td>
                </tr>
            </table>

            <pre class="bg-dark text-white" v-if="entry.content.status == 'failed'">
                <p v-for="(content, number) in entry.content.exception.line_preview" class="mb-0" :class="{'text-danger': number == entry.content.exception.line}">{{number}} {{content}}</p>
            </pre>

            <div class="card">
                <code>
                    {{entry.content.data}}
                </code>
            </div>
        </div>
    </loader>
</template>

<style scoped>
</style>