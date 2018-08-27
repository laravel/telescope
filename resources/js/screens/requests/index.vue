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
            document.title = "Requests - Telescope";

            axios.get('/telescope/telescope-api/requests').then(response => {
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
                <th scope="col">Method</th>
                <th scope="col">Path</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="entry in entries">
                <td>{{entry.content.method}}</td>
                <td>{{entry.content.uri}}</td>
                <td>
                    <router-link :to="{name:'requests-preview', params:{id: entry.id}}"
                                 class="btn btn-sm btn-primary">Preview
                    </router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </loader>
</template>
