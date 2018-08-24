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
                entries: [],
                ready: false,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Events - Telescope";

            axios.get('/telescope/telescope-api/events').then(response => {
                this.entries = response.data.entries;

                this.ready = true;
            })
        },
    }
</script>

<template>
    <loader :loading="!ready">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Internal</th>
                <th scope="col">Time</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="entry in entries">
                <td>{{entry.content.event_name}}</td>
                <td>{{entry.content.is_internal}}</td>
                <td>{{entry.created_at}}</td>
                <td>
                    <router-link :to="{name:'event-preview', params:{id: entry.id}}"
                                 class="btn btn-sm btn-primary">Preview
                    </router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </loader>
</template>
