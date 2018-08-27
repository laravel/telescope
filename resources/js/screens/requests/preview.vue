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
            document.title = "Requests - Telescope";

            axios.get('/telescope/telescope-api/requests/' + this.$route.params.id).then(response => {
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
                    <td class="font-weight-bold pl-0">Path</td>
                    <td>{{entry.content.uri}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Method</td>
                    <td>{{entry.content.method}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Status</td>
                    <td>{{entry.content.response_status}}</td>
                </tr>
            </table>

            {{entry.content.payload}}
            {{entry.content.response}}
            {{entry.content.headers}}
        </div>
    </loader>
</template>

<style scoped>
</style>