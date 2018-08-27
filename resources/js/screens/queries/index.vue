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
            document.title = "Queries - Telescope";

            axios.get('/telescope/telescope-api/queries').then(response => {
                this.entries = response.data.entries;

                this.ready = true;
            })
        },

        methods: {
            /**
             * Truncate the given exception message.
             */
            truncateSql(message){
                return _.truncate(message, {
                    'length': 120,
                    'separator': /,? +/
                });
            }
        }
    }
</script>

<template>
    <loader :loading="!ready">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Connection</th>
                <th scope="col">Message</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="entry in entries">
                <td>{{entry.content.connection}}</td>
                <td>{{truncateSql(entry.content.sql)}}</td>
                <td>
                    <router-link :to="{name:'queries-preview', params:{id: entry.id}}"
                                 class="btn btn-sm btn-primary">Preview
                    </router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </loader>
</template>
