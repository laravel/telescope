<script type="text/ecmascript-6">
import _ from "lodash";

export default {
  props: ["trace"],

  /**
   * The component's data.
   */
  data() {
    return {
      minimumLines: 5,
      showAll: false,
      openedArgs: []
    };
  },

  computed: {
    lines() {
      return this.showAll
        ? _.take(this.trace, 1000)
        : _.take(this.trace, this.minimumLines);
    }
  },

  methods: {
    toggleArgs(id) {
      const index = this.openedArgs.indexOf(id);
      if (index > -1) {
        this.openedArgs.splice(index, 1);
      } else {
        this.openedArgs.push(id);
      }
    }
  }
};
</script>

<template>
  <table class="table mb-0">
    <tbody>
      <tr v-for="line in lines">
        <td class="card-bg-secondary">

          <div>{{line.file}}:{{line.line}}</div>

          <div>
            {{ line.class }}@{{ line.function }}
            <a v-if="line.args.length" href="*" v-on:click.prevent="toggleArgs(line.line)" >arguments</a>
          </div>
          
          <table class="table table-hover table-sm mb-0" v-show="openedArgs.includes(line.line)">
            <tr v-for="(argument, index) in line.args">
              <td>
                  {{ index }}
              </td>
              <td>
                <pre class="mb-0">{{ argument }}</pre>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr v-if="! showAll">
        <td class="card-bg-secondary">
          <a href="*" v-on:click.prevent="showAll = true">Show All</a>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<style scoped>
</style>