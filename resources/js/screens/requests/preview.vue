<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
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
            <td class="table-fit font-weight-bold">Method</td>
            <td>
                {{slotProps.entry.content.method}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Controller Action</td>
            <td>
                {{slotProps.entry.content.controller_action}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Middleware</td>
            <td>
                {{slotProps.entry.content.middleware.join(", ")}}
            </td>
        </tr>
        
        <tr>
            <td class="table-fit font-weight-bold">Path</td>
            <td>
                {{slotProps.entry.content.uri}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Status</td>
            <td>
                {{slotProps.entry.content.response_status}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Duration</td>
            <td>
                {{slotProps.entry.content.duration || '-'}} ms
            </td>
        </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
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
                    <tree-view :data="slotProps.entry.content.payload" :options="{maxDepth: 3}" v-if="currentTab=='payload'"></tree-view>
                    <tree-view :data="slotProps.entry.content.headers" :options="{maxDepth: 3}" v-if="currentTab=='headers'"></tree-view>
                    <tree-view :data="slotProps.entry.content.session" :options="{maxDepth: 3}" v-if="currentTab=='session'"></tree-view>
                    <tree-view :data="slotProps.entry.content.response" :options="{maxDepth: 3}" v-if="currentTab=='response'"></tree-view>
                </div>
            </div>

            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch">
            </related-entries>
        </div>
    </preview-screen>
</template>
