<script type="text/ecmascript-6">
    import _ from 'lodash';
    import axios from 'axios';
    import $ from 'jquery';

    export default {
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
            document.title = "Cache - Telescope";

            axios.get('/telescope/telescope-api/cache').then(response => {
                this.entries = response.data.entries;

                this.ready = true;
            })
        }
    }
</script>

<template>
    <loader :loading="!ready">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Action</th>
                <th scope="col">Key</th>
                <th scope="col">Time</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="entry in entries">
                <td>{{entry.content.type}}</td>
                <td>{{entry.content.key}}</td>
                <td>{{entry.created_at}}</td>
                <td>
                    <router-link :to="{name:'cache-preview', params:{id: entry.id}}"
                                 class="btn btn-sm btn-primary">Preview
                    </router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </loader>
</template>
