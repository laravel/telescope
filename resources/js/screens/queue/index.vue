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
            document.title = "Queue - Telescope";

            axios.get('/telescope/telescope-api/queue').then(response => {
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
                <th scope="col">Status</th>
                <th scope="col">Name</th>
                <th scope="col">Queue</th>
                <th scope="col">Connection</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="entry in entries">
                <td>{{entry.content.status}}</td>
                <td>{{entry.content.name}}</td>
                <td>{{entry.content.queue}}</td>
                <td>{{entry.content.connection}}</td>
                <td>
                    <router-link :to="{name:'queue-preview', params:{id: entry.id}}"
                                 class="btn btn-sm btn-primary">Preview
                    </router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </loader>
</template>
