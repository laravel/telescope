<script type="text/ecmascript-6">
    import _ from 'lodash';
    import StylesMixin from './../../mixins/entriesStyles';

    export default {
        mixins: [
            StylesMixin,
        ],


        data(){
            return {
                entry: null,
                batch: [],
                currentTab: 'data'
            };
        },
    }
</script>

<template>
    <preview-screen title="View Action" resource="views" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">View</td>
                <td>
                    {{slotProps.entry.content.name}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Path</td>
                <td>
                    {{slotProps.entry.content.path}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.data">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='data'}" href="#" v-on:click.prevent="currentTab='data'">Data</a>
                    </li>
                    <li class="nav-item" v-if="slotProps.entry.content.composers">
                        <a class="nav-link" :class="{active: currentTab=='composers'}" href="#" v-on:click.prevent="currentTab='composers'">Composers</a>
                    </li>
                </ul>
                <div>
                    <!-- View Payload -->
                    <div class="code-bg p-4 mb-0 text-white" v-show="currentTab=='data'">
                        <vue-json-pretty :data="slotProps.entry.content.data"></vue-json-pretty>
                    </div>

                    <!-- View Composers -->
                    <table class="table table-hover mb-0" v-show="currentTab=='composers'">
                        <thead>
                        <tr>
                            <th>Composer</th>
                            <th>Type</th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr v-for="(composer, key) in slotProps.entry.content.composers" :key="key">
                            <td :title="composer.name">{{composer.name}}</td>
                            <td class="table-fit">
                                <span class="badge" :class="'badge-'+composerTypeClass(composer.type)">
                                    {{composer.type}}
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
