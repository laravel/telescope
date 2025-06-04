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
                <td class="table-fit text-muted">Command</td>
                <td>
                    <code>{{ slotProps.entry.content.command }}</code>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Exit Code</td>
                <td>
                    {{ slotProps.entry.content.exit_code }}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentTab == 'arguments' }"
                            href="#"
                            v-on:click.prevent="currentTab = 'arguments'"
                            >Arguments</a
                        >
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentTab == 'options' }"
                            href="#"
                            v-on:click.prevent="currentTab = 'options'"
                            >Options</a
                        >
                    </li>
                </ul>
                <div>
                    <div class="code-bg p-4 mb-0 text-white">
                        <copy-clipboard :data="slotProps.entry.content[currentTab]">
                            <vue-json-pretty :data="slotProps.entry.content[currentTab]"></vue-json-pretty>
                        </copy-clipboard>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch"> </related-entries>
        </div>
    </preview-screen>
</template>
