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
            document.title = "Cache - Telescope";

            axios.get('/telescope/telescope-api/cache/' + this.$route.params.id).then(response => {
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
                    <td class="font-weight-bold pl-0">Action</td>
                    <td>{{entry.content.type}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Key</td>
                    <td>{{entry.content.key}}</td>
                </tr>

                <tr v-if="entry.content.expiration">
                    <td class="font-weight-bold pl-0">Expiration</td>
                    <td>{{entry.content.expiration}}</td>
                </tr>
            </table>

            <div class="card" v-if="entry.content.value">
                <code>
                    {{entry.content.value}}
                </code>
            </div>
        </div>
    </loader>
</template>

<style scoped>
</style>