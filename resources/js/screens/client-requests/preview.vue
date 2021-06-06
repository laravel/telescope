<script type="text/ecmascript-6">
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
    <preview-screen title="Http Client Request Details" resource="client-requests" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Method</td>
            <td>
                {{slotProps.entry.content.method}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Uri</td>
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
                        <a class="nav-link" :class="{active: currentTab=='response-headers'}" href="#" v-on:click.prevent="currentTab='response-headers'">Response Headers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='response-body'}" href="#" v-on:click.prevent="currentTab='response-body'">Response Body</a>
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <vue-json-pretty :data="slotProps.entry.content.payload" v-if="currentTab=='payload'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.headers" v-if="currentTab=='headers'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.response_headers" v-if="currentTab=='response-headers'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.response" v-if="currentTab=='response-body'"></vue-json-pretty>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
