<script type="text/ecmascript-6">
  export default {
    data(){
      return {
        entry: null,
        batch: [],
        currentTab: 'data'
      };
    },

    methods: {
      entryTypeClass(method) {
        return 'info';
      },
    }
  }
</script>

<template>
    <preview-screen title="Timing Details" resource="timing" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Total Duration</td>
                <td>
                    {{slotProps.entry.content.duration}} ms
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='data'}" href="#" v-on:click.prevent="currentTab='data'">Timing Data</a>
                    </li>
                </ul>
                <div>
                    <table  class="table mb-0">
                        <tbody>
                            <tr v-for="timing in batch" :key="timing.id">
                                <td class="table-fit pr-0">
                                <span class="badge font-weight-light" :class="'badge-'+entryTypeClass(timing.entryType)">
                                    {{timing.entryType}}
                                </span>
                                </td>

                                <td :title="timing.duration + 'ms: ' + timing.label" width="100%">
                                    <div :class="'timing-event timing-'+entryTypeClass(timing.entryType)" :style="{ marginLeft: timing.left + '%', width: timing.width + '%' }"></div>
                                </td>

                                <td class="table-fit">
                                    <router-link :to="{name: timing.entryType + '-preview', params:{id: timing.id}}" class="control-action">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                            <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                                        </svg>
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </preview-screen>
</template>

<style>
    .timing-event {
        height: 20px;
        background-color: grey;
        padding: 0 1px;
    }
</style>
