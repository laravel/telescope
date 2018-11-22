<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        data(){
            return {
                entry: null,
                batch: [],
                    currentTab: 'arguments'
            };
        }
    }
</script>

<template>
    <preview-screen title="Command Details" resource="commands" :id="$route.params.id" entry-point="true">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Command</td>
                <td>
                    {{slotProps.entry.content.command}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Exit Code</td>
                <td>
                    {{slotProps.entry.content.exit_code}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='arguments'}" href="#" v-on:click.prevent="currentTab='arguments'">Arguments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='options'}" href="#" v-on:click.prevent="currentTab='options'">Options</a>
                    </li>
                </ul>
                <div>
                    <div class="code-bg p-4 mb-0 text-white" v-show="currentTab=='arguments'">
                        <vue-json-pretty :data="slotProps.entry.content.arguments"></vue-json-pretty>
                    </div>
                    <div class="code-bg p-4 mb-0 text-white" v-show="currentTab=='options'">
                        <vue-json-pretty :data="slotProps.entry.content.options"></vue-json-pretty>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch">
            </related-entries>
        </div>
    </preview-screen>
</template>
