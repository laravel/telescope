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
            currentTab: 'payload',
        };
    }
}
</script>

<template>
    <preview-screen title="Security Details" resource="security" :id="$route.params.id" entry-point="true">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Method</td>
                <td>
                    <span class="badge" :class="'badge-' + requestMethodClass(slotProps.entry.content.method)">
                        {{ slotProps.entry.content.method }}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Path</td>
                <td>
                    {{ slotProps.entry.content.path || slotProps.entry.content.uri }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Full URI</td>
                <td>
                    {{ slotProps.entry.content.uri }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Status</td>
                <td>
                    <span class="badge badge-danger">
                        Suspicious
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">IP Address</td>
                <td>
                    {{ slotProps.entry.content.ip_address || '-' }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">User Agent</td>
                <td>
                    {{ slotProps.entry.content.user_agent || '-' }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Response Status</td>
                <td>
                    <span class="badge" :class="'badge-' + requestStatusClass(slotProps.entry.content.response_status)">
                        {{ slotProps.entry.content.response_status }}
                    </span>
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.suspicious_reasons && slotProps.entry.content.suspicious_reasons.length">
                <td class="table-fit text-muted">Suspicious Reasons</td>
                <td>
                    <ul class="mb-0">
                        <li v-for="(reason, index) in slotProps.entry.content.suspicious_reasons" :key="index">
                            <span class="badge badge-warning">{{ reason }}</span>
                        </li>
                    </ul>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentTab == 'payload' }"
                            href="#"
                            v-on:click.prevent="currentTab = 'payload'"
                            >Payload</a
                        >
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentTab == 'query_parameters' }"
                            href="#"
                            v-on:click.prevent="currentTab = 'query_parameters'"
                            >Query Parameters</a
                        >
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentTab == 'headers' }"
                            href="#"
                            v-on:click.prevent="currentTab = 'headers'"
                            >Headers</a
                        >
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <copy-clipboard :data="slotProps.entry.content[currentTab]">
                        <vue-json-pretty :data="slotProps.entry.content[currentTab]"></vue-json-pretty>
                    </copy-clipboard>
                </div>
            </div>

            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch"> </related-entries>
        </div>
    </preview-screen>
</template>


