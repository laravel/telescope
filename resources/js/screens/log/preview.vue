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
            document.title = "Log Entries - Telescope";

            axios.get('/telescope/telescope-api/log/' + this.$route.params.id).then(response => {
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
                    <td class="font-weight-bold pl-0">Level</td>
                    <td>{{entry.content.level}}</td>
                </tr>

                <tr v-if="entry.content.exception">
                    <td class="font-weight-bold pl-0">File</td>
                    <td>
                        {{entry.content.exception.file}}:{{entry.content.exception.line}}
                    </td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Message</td>
                    <td>{{entry.content.message}}</td>
                </tr>
            </table>

            <pre class="bg-dark text-white" v-if="entry.content.exception">
                <p v-for="(content, number) in entry.content.exception.line_preview" class="mb-0" :class="{'text-danger': number == entry.content.exception.line}">{{number}} {{content}}</p>
            </pre>

            <ul v-if="entry.content.exception && entry.content.exception.trace.length">
                <li v-for="line in entry.content.exception.trace">
                    {{line.file}}:{{line.line}}
                </li>
            </ul>

            {{entry.content.context}}
        </div>
    </loader>
</template>

<style scoped>
</style>