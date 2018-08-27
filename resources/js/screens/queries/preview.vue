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
            document.title = "Queries - Telescope";

            axios.get('/telescope/telescope-api/queries/' + this.$route.params.id).then(response => {
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
                    <td class="font-weight-bold pl-0">Time</td>
                    <td>{{entry.created_at}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Connection</td>
                    <td>{{entry.content.connection}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Duration</td>
                    <td>{{entry.content.time}}</td>
                </tr>
            </table>

            <pre class="bg-dark text-white" v-if="entry.content.sql">
                {{entry.content.sql}}
            </pre>

            {{entry.content.bindings}}
        </div>
    </loader>
</template>

<style scoped>
</style>