<script type="text/ecmascript-6">
import axios from 'axios';
import StylesMixin from './../../mixins/entriesStyles';

export default {
    components: {},


    mixins: [
        StylesMixin,
    ],


    data() {
        return {
            entry: null,
            batch: [],
            currentTab: 'data'
        };
    }
}
</script>

<template>
    <preview-screen title="Batch Details" resource="batches" :id="$route.params.id" entry-point="true">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Status</td>
                <td>
                    <small
                        class="badge badge-danger badge-sm"
                        v-if="slotProps.entry.content.failedJobs > 0 && slotProps.entry.content.progress < 100"
                    >
                        Failures
                    </small>
                    <small class="badge badge-success badge-sm" v-if="slotProps.entry.content.progress == 100">
                        Finished
                    </small>
                    <small
                        class="badge badge-secondary badge-sm"
                        v-if="
                            slotProps.entry.content.totalJobs == 0 ||
                            (slotProps.entry.content.pendingJobs > 0 && !slotProps.entry.content.failedJobs)
                        "
                    >
                        Pending
                    </small>
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.cancelledAt">
                <td class="table-fit text-muted">Cancelled At</td>
                <td>
                    {{ localTime(slotProps.entry.content.cancelledAt) }} ({{
                        timeAgo(slotProps.entry.content.cancelledAt)
                    }})
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.finishedAt">
                <td class="table-fit text-muted">Finished At</td>
                <td>
                    {{ localTime(slotProps.entry.content.finishedAt) }} ({{
                        timeAgo(slotProps.entry.content.finishedAt)
                    }})
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Batch</td>
                <td>
                    {{ slotProps.entry.content.name || slotProps.entry.content.id }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Connection</td>
                <td>
                    {{ slotProps.entry.content.connection }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Queue</td>
                <td>
                    {{ slotProps.entry.content.queue }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Size</td>
                <td>
                    <router-link
                        :to="{
                            name: 'jobs',
                            query: { family_hash: slotProps.entry.family_hash },
                        }"
                        class="control-action"
                    >
                        {{ slotProps.entry.content.totalJobs }} Jobs
                    </router-link>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Pending</td>
                <td>
                    {{ slotProps.entry.content.pendingJobs }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Progress</td>
                <td>{{ slotProps.entry.content.progress }}%</td>
            </tr>
        </template>
    </preview-screen>
</template>

<style scoped></style>
