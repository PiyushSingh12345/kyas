<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Master Data</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="#">Budget Phase</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Budget Phase</div>
                </div>

                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label for="financialYear">F.Y</label>
                        <select class="form-select" id="financialYear" v-model="financialYear">
                          <option value="2025-26">2025-26</option>
                          <option value="2024-25">2024-25</option>
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
                          <option value="RE">RE</option>
                          <option value="FE">FE</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- No data message -->
                  <div v-if="selectedPhase !== '0' && filteredBudgetHeads.length === 0" class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>No budget data found</strong> for the selected Financial Year ({{ financialYear }}) and Budget Phase ({{ selectedPhase }}).
                    <br>Please ensure that budget heads are available for this combination.
                  </div>

                  <div v-if="filteredBudgetHeads.length !== 0" class="table-responsive mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="rscss">(In Lakhs)</span>
                      <div class="alert alert-info py-2 px-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Total Budget Amount:</strong> 
                        <span class="fw-bold text-primary">{{ totalBudgetAmount.toLocaleString() }}</span> Lakhs
                        <span class="text-muted ms-2">({{ filteredBudgetHeads.length }} budget heads)</span>
                      </div>
                    </div>
                    
                    <!-- Budget Summary Card -->
                    <div class="row mb-3">
                      <div class="col-md-4">
                        <div class="card bg-primary text-white">
                          <div class="card-body text-center">
                            <h5 class="card-title">Total Budget</h5>
                            <h3 class="mb-0">{{ totalBudgetAmount.toLocaleString() }} Lakhs</h3>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="card bg-success text-white">
                          <div class="card-body text-center">
                            <h5 class="card-title">Allocated Amount</h5>
                            <h3 class="mb-0">{{ allocatedAmount.toLocaleString() }} Lakhs</h3>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="card bg-warning text-white">
                          <div class="card-body text-center">
                            <h5 class="card-title">Remaining Amount</h5>
                            <h3 class="mb-0">{{ remainingAmount.toLocaleString() }} Lakhs</h3>
                          </div>
                        </div>
                      </div>
                    </div>
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
                            <!-- {{ item }} -->
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
                            <div class="d-flex align-items-center">
                              <input
                                v-if="item.draft_flag === 0"
                                type="number"
                                v-model="item.amount"
                                class="form-control tableform-control-withoutbg"
                                placeholder="Enter amount"
                              />
                              <input
                                v-else
                                type="number"
                                :value="item.amount"
                                class="form-control tableform-control-withoutbg fw-bold text-success"
                                readonly
                              />
                              <span v-if="item.amount && item.amount > 0" class="ms-2 badge bg-success">
                                <i class="fas fa-check"></i>
                              </span>
                              <span v-else class="ms-2 badge bg-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                              </span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot class="table-dark">
                        <tr>
                          <td colspan="2" class="text-end fw-bold">Total Budget Amount:</td>
                          <td class="fw-bold text-success">{{ totalBudgetAmount.toLocaleString() }} Lakhs</td>
                        </tr>
                      </tfoot>
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
import { ref, reactive, computed } from 'vue'
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
    const financialYear = ref('2025-26')
    const filteredBudgetHeads = ref([])
    const isSubmitted = ref(false)

    const fetchBudgetHeads = async () => 
    {
      if (selectedPhase.value === '0') {
        filteredBudgetHeads.value = []
        return
      }

      try {
        console.log("Fetching budget heads for phase:", selectedPhase.value, "and year:", financialYear.value);
        const response = await fetch(`/api/budget-heads?phase=${selectedPhase.value}&year=${financialYear.value}`)
        const data = await response.json()
        filteredBudgetHeads.value = data
        console.log("Fetched budget heads:", data);
        console.log("Sample item structure:", data[0]);

        // If ALL draft_flag === 1, disable buttons
        isSubmitted.value = data.every(item => item.draft_flag === 1)
      } catch (error) {
        console.error('Error fetching budget heads:', error)
      }
    }



    const saveAsDraft = () => {
      const allocations = filteredBudgetHeads.value
        .filter(item => item.draft_flag === 0 && item.amount !== null && item.amount !== '' && item.amount !== undefined)
        .map(item => ({
          financial_year: financialYear.value,
          budget_phase: selectedPhase.value,
          budget_head_id: item.id,
          budget_amount: item.amount,
          status: 1,
          draft_flag: 0
        }))

      console.log("Filtered draft allocations:", allocations)

      router.post(route('budget-phase.store'), { allocations }, {
        preserveScroll: true,
        onSuccess: () => {
          isSubmitted.value = true
        },
        onError: (errors) => {
          console.error("Validation errors:", errors);
        }
      })
    }


  const submit = () => {
    const allocations = filteredBudgetHeads.value
      .filter(item => item.draft_flag === 0 && item.amount !== null && item.amount !== '' && item.amount !== undefined)
      .map(item => ({
        financial_year: financialYear.value,
        budget_phase: selectedPhase.value,
        budget_head_id: item.id,
        budget_amount: item.amount,
        status: 1,
        draft_flag: 1
      }))

    console.log("Filtered submitted allocations:", allocations)

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

    // Computed property for total budget amount
    const totalBudgetAmount = computed(() => {
      return filteredBudgetHeads.value.reduce((total, item) => {
        return total + (parseFloat(item.amount) || 0)
      }, 0)
    })

    // Computed property for allocated amount (amounts that are not null/empty)
    const allocatedAmount = computed(() => {
      return filteredBudgetHeads.value.reduce((total, item) => {
        return total + (parseFloat(item.amount) || 0)
      }, 0)
    })

    // Computed property for remaining amount (budget heads with no amount)
    const remainingAmount = computed(() => {
      const budgetHeadsWithAmount = filteredBudgetHeads.value.filter(item => 
        item.amount !== null && item.amount !== '' && item.amount !== undefined
      ).length
      const totalBudgetHeads = filteredBudgetHeads.value.length
      return totalBudgetHeads - budgetHeadsWithAmount
    })

    return {
      selectedPhase,
      financialYear,
      filteredBudgetHeads,
      isSubmitted,
      totalBudgetAmount,
      allocatedAmount,
      remainingAmount,
      fetchBudgetHeads,
      saveAsDraft,
      submit,
      reset
    }
  }
}
</script>
