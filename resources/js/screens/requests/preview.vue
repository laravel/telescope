<script type="text/ecmascript-6">
    import StylesMixin from './../../mixins/entriesStyles';

    export default {
        mixins: [
            StylesMixin,
        ],

        data() {
            return {
                entry: null,
                batch: [],
                currentTab: 'payload'
            };
        }
    }
</script>

<template>
    <preview-screen title="Request Details" resource="requests" :id="$route.params.id" entry-point="true">
        <template slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit text-muted">Method</td>
            <td>
                <span class="badge" :class="'badge-'+requestMethodClass(slotProps.entry.content.method)">
                    {{slotProps.entry.content.method}}
                </span>
            </td>
        </tr>

        <tr>
            <td class="table-fit text-muted">Controller Action</td>
            <td>
                {{slotProps.entry.content.controller_action}}
            </td>
        </tr>

        <tr v-if="slotProps.entry.content.middleware">
            <td class="table-fit text-muted">Middleware</td>
            <td>
                {{slotProps.entry.content.middleware.join(", ")}}
            </td>
        </tr>

        <tr>
            <td class="table-fit text-muted">Path</td>
            <td>
                {{slotProps.entry.content.uri}}
            </td>
        </tr>

        <tr>
            <td class="table-fit text-muted">Status</td>
            <td>
                <span class="badge" :class="'badge-'+requestStatusClass(slotProps.entry.content.response_status)">
                    {{slotProps.entry.content.response_status}}
                </span>
            </td>
        </tr>

        <tr>
            <td class="table-fit text-muted">Duration</td>
            <td>
                {{slotProps.entry.content.duration || '-'}} ms
            </td>
        </tr>

        <tr>
            <td class="table-fit text-muted">IP Address</td>
            <td>
                {{slotProps.entry.content.ip_address || '-'}}
            </td>
        </tr>

        <tr>
            <td class="table-fit text-muted">Memory usage</td>
            <td>
                {{slotProps.entry.content.memory || '-'}} MB
            </td>
        </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='payload'}" href="#" v-on:click.prevent="currentTab='payload'">Payload</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='headers'}" href="#" v-on:click.prevent="currentTab='headers'">Headers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='session'}" href="#" v-on:click.prevent="currentTab='session'">Session</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='response'}" href="#" v-on:click.prevent="currentTab='response'">Response</a>
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <vue-json-pretty :data="slotProps.entry.content.payload" v-if="currentTab=='payload'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.headers" v-if="currentTab=='headers'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.session" v-if="currentTab=='session'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.response" v-if="currentTab=='response'"></vue-json-pretty>
                </div>
            </div>

            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch">
            </related-entries>
        </div>
    </preview-screen>
</template>
