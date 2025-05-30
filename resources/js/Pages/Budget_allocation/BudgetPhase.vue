<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold">Budget Allocation Module</h3>
            <ul class="breadcrumbs">
              <li class="nav-home">
                <a href="login.html">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add details of BE/FE/RE</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Add details of BE/FE/RE</div>
                </div>

                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label for="financialYear">F.Y</label>
                        <select class="form-select" id="financialYear" v-model="financialYear">
                          <option value="2024-2025">2024–2025</option>
                          <!-- Add more years if needed -->
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label for="budgetPhase">Budget Phase</label>
                        <select class="form-select" id="budgetPhase" v-model="selectedPhase" @change="fetchBudgetHeads">
                          <option disabled value="0">Select Budget Phase</option>
                          <option value="BE">BE</option>
                          <option value="FE">FE</option>
                          <option value="RE">RE</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div v-if="filteredBudgetHeads.length !== 0" class="table-responsive mt-3">
                    <a class="rscss">(In Lakhs)</a>
                    <table class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th>Budget Head</th>
                          <th>Head Description</th>
                          <th>Budget Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in filteredBudgetHeads" :key="item.id">
                          <td>
                            <input
                              type="text"
                              v-model="item.budget"
                              class="form-control tableform-control-withoutbg"
                              disabled
                            />
                          </td>
                          <td>
                            <input
                              type="text"
                              v-model="item.description"
                              class="form-control tableform-control-withoutbg"
                              disabled
                            />
                          </td>
                          <td>
                            <input
                              v-if="item.draft_flag === 0"
                              type="number"
                              v-model="item.amount"
                              class="form-control tableform-control-withoutbg"
                            />
                            <input
                              v-else
                              type="number"
                              :value="item.amount"
                              class="form-control tableform-control-withoutbg"
                              readonly
                            />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div> <!-- end of card-body -->

                <div class="card-footer" v-if="filteredBudgetHeads.length !== 0">
                  <div class="form">
                    <div class="col-12 d-flex justify-content-center">
                      <button id="displayNotif" class="btn btn-primary me-1" @click="saveAsDraft" :disabled="isSubmitted">Save as Draft</button>
                      <button class="btn btn-success me-1" @click="submit" :disabled="isSubmitted">Submit</button>
                      <button class="btn btn-danger me-1" @click="reset" :disabled="isSubmitted">Reset</button>
                    </div>
                  </div>
                </div> <!-- end of card-footer -->

              </div> <!-- end of card -->
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  </div>
</template>

<script>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, reactive } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export default {
  name: 'BudgetPhase',
  components: {
    Header,
    Sidebar,
    Footer
  },
  setup() {
    const selectedPhase = ref('0')
    const financialYear = ref('2024-2025')
    const filteredBudgetHeads = ref([])
    const isSubmitted = ref(false)

    const fetchBudgetHeads = async () => 
    {
      if (selectedPhase.value === '0') {
        filteredBudgetHeads.value = []
        return
      }

      try {
        const response = await fetch(`/api/budget-heads?phase=${selectedPhase.value}&year=${financialYear.value}`)
        const data = await response.json()
        filteredBudgetHeads.value = data

        // If ALL draft_flag === 1, disable buttons
        isSubmitted.value = data.every(item => item.draft_flag === 1)
      } catch (error) {
        console.error('Error fetching budget heads:', error)
      }
    }



    const saveAsDraft = () => {
      const allocations = filteredBudgetHeads.value
        .filter(item => item.draft_flag === 0)
        .map(item => ({
          financial_year: financialYear.value,
          budget_phase: selectedPhase.value,
          budget_head_id: item.id,
          budget_amount: item.amount,
          status: 1,
          draft_flag: 0
        }))

      router.post(route('budget-phase.store'), { allocations }, {
        onSuccess: () => {
          isSubmitted.value = false
        }
      })
    }

    const submit = () => {
      const allocations = filteredBudgetHeads.value
        .filter(item => item.draft_flag === 0)
        .map(item => ({
          financial_year: financialYear.value,
          budget_phase: selectedPhase.value,
          budget_head_id: item.id,
          budget_amount: item.amount,
          status: 1,
          draft_flag: 1
        }))

      router.post(route('budget-phase.store'), { allocations }, {
        onSuccess: () => {
          isSubmitted.value = true
        }
      })
    }


    const reset = () => {
      filteredBudgetHeads.value.forEach(item => {
        item.amount = null
      })
    }

    return {
      selectedPhase,
      financialYear,
      filteredBudgetHeads,
      isSubmitted,
      fetchBudgetHeads,
      saveAsDraft,
      submit,
      reset
    }
  }
}
</script>
