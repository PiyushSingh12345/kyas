import { ref, onMounted, onBeforeUnmount, onUpdated, nextTick } from 'vue'
import '../../css/fixed-horizontal-scroll.css'

export function useFixedHorizontalScroll(options = {}) {
  const { shouldUpdate = () => true } = options

  const reportTableScrollWrapper = ref(null)
  const fixedScrollBar = ref(null)
  const fixedScrollBarInner = ref(null)
  const showFixedScrollBar = ref(false)
  let scrollSyncLock = false

  function updateFixedScrollBarWidth() {
    nextTick(() => {
      const wrapper = reportTableScrollWrapper.value
      const inner = fixedScrollBarInner.value
      const bar = fixedScrollBar.value
      if (!wrapper || !inner || !bar) return
      const tableEl = wrapper.querySelector('#reportTable table') || wrapper.querySelector('table')
      let contentWidth = tableEl && tableEl.scrollWidth > 0 ? tableEl.scrollWidth : wrapper.scrollWidth
      if (contentWidth <= 0) contentWidth = wrapper.scrollWidth
      const cw = wrapper.clientWidth
      inner.style.width = contentWidth + 'px'
      showFixedScrollBar.value = contentWidth > cw
      if (showFixedScrollBar.value) {
        const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
        const barMax = bar.scrollWidth - bar.clientWidth
        scrollSyncLock = true
        if (barMax > 0 && wrapperMax > 0) {
          bar.scrollLeft = (wrapper.scrollLeft / wrapperMax) * barMax
        } else {
          bar.scrollLeft = wrapper.scrollLeft
        }
        scrollSyncLock = false
      }
    })
  }

  function onTableWrapperScroll() {
    if (scrollSyncLock) return
    const wrapper = reportTableScrollWrapper.value
    const bar = fixedScrollBar.value
    if (!wrapper || !bar) return
    const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
    const barMax = bar.scrollWidth - bar.clientWidth
    if (wrapperMax <= 0 || barMax <= 0) return
    scrollSyncLock = true
    bar.scrollLeft = (wrapper.scrollLeft / wrapperMax) * barMax
    scrollSyncLock = false
  }

  function onFixedScrollBarScroll() {
    if (scrollSyncLock) return
    const wrapper = reportTableScrollWrapper.value
    const bar = fixedScrollBar.value
    if (!wrapper || !bar) return
    const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
    const barMax = bar.scrollWidth - bar.clientWidth
    if (wrapperMax <= 0 || barMax <= 0) return
    scrollSyncLock = true
    wrapper.scrollLeft = (bar.scrollLeft / barMax) * wrapperMax
    scrollSyncLock = false
  }

  function refreshFixedHorizontalScroll() {
    nextTick(updateFixedScrollBarWidth)
    setTimeout(updateFixedScrollBarWidth, 300)
  }

  onMounted(() => {
    window.addEventListener('resize', updateFixedScrollBarWidth)
    refreshFixedHorizontalScroll()
  })

  onUpdated(() => {
    if (shouldUpdate()) updateFixedScrollBarWidth()
  })

  onBeforeUnmount(() => {
    window.removeEventListener('resize', updateFixedScrollBarWidth)
  })

  return {
    reportTableScrollWrapper,
    fixedScrollBar,
    fixedScrollBarInner,
    showFixedScrollBar,
    onTableWrapperScroll,
    onFixedScrollBarScroll,
    updateFixedScrollBarWidth,
    refreshFixedHorizontalScroll,
  }
}
