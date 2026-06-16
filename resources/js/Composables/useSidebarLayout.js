import { ref, onMounted, onUnmounted } from 'vue'

const sidebarMinimized = ref(false)
const navOpen = ref(false)
const topbarOpen = ref(false)
let hoverExpanded = false

function getWrapper() {
  return document.querySelector('.wrapper')
}

function onSidebarMouseEnter() {
  const wrapper = getWrapper()
  if (!wrapper) return

  if (sidebarMinimized.value && !hoverExpanded) {
    wrapper.classList.add('sidebar_minimize_hover')
    hoverExpanded = true
  } else {
    wrapper.classList.remove('sidebar_minimize_hover')
  }
}

function onSidebarMouseLeave() {
  const wrapper = getWrapper()
  if (!wrapper) return

  if (sidebarMinimized.value && hoverExpanded) {
    wrapper.classList.remove('sidebar_minimize_hover')
    hoverExpanded = false
  }
}

let hoverListenersAttached = false
let hoverSidebars = []

function attachSidebarHoverListeners() {
  if (hoverListenersAttached) return

  hoverSidebars = Array.from(document.querySelectorAll('.sidebar'))
  if (hoverSidebars.length === 0) return

  hoverSidebars.forEach((sidebar) => {
    sidebar.addEventListener('mouseenter', onSidebarMouseEnter)
    sidebar.addEventListener('mouseleave', onSidebarMouseLeave)
  })
  hoverListenersAttached = true
}

function detachSidebarHoverListeners() {
  if (!hoverListenersAttached) return

  hoverSidebars.forEach((sidebar) => {
    sidebar.removeEventListener('mouseenter', onSidebarMouseEnter)
    sidebar.removeEventListener('mouseleave', onSidebarMouseLeave)
  })
  hoverSidebars = []

  hoverListenersAttached = false
}

export function useSidebarLayout() {
  function toggleSidebar() {
    const wrapper = getWrapper()
    if (!wrapper) return

    sidebarMinimized.value = !sidebarMinimized.value
    wrapper.classList.toggle('sidebar_minimize', sidebarMinimized.value)
    window.dispatchEvent(new Event('resize'))
  }

  function toggleSidenav() {
    navOpen.value = !navOpen.value
    document.documentElement.classList.toggle('nav_open', navOpen.value)
    document.querySelectorAll('.sidenav-toggler').forEach((btn) => {
      btn.classList.toggle('toggled', navOpen.value)
    })
  }

  function toggleTopbar() {
    topbarOpen.value = !topbarOpen.value
    document.documentElement.classList.toggle('topbar_open', topbarOpen.value)
    document.querySelectorAll('.topbar-toggler').forEach((btn) => {
      btn.classList.toggle('toggled', topbarOpen.value)
    })
  }

  onMounted(() => {
    const wrapper = getWrapper()
    if (wrapper?.classList.contains('sidebar_minimize')) {
      sidebarMinimized.value = true
    }

    attachSidebarHoverListeners()
  })

  onUnmounted(() => {
    detachSidebarHoverListeners()
  })

  return {
    sidebarMinimized,
    navOpen,
    topbarOpen,
    toggleSidebar,
    toggleSidenav,
    toggleTopbar,
  }
}
