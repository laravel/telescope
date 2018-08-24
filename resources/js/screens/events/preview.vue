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
            document.title = "Events - Telescope";

            axios.get('/telescope/telescope-api/events/' + this.$route.params.id).then(response => {
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
                    <td class="font-weight-bold pl-0">Name</td>
                    <td>{{entry.content.event_name}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Internal</td>
                    <td>{{entry.content.is_internal}}</td>
                </tr>
            </table>

            <div class="card">
                <code>
                    {{entry.content.event_payload}}
                </code>
            </div>

            <ul v-if="entry.content.listeners && entry.content.listeners.length">
                <li v-for="listener in entry.content.listeners">
                    {{listener}}
                </li>
            </ul>
        </div>
    </loader>
</template>

<style scoped>
</style>