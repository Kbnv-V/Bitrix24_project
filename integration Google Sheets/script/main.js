var app = new Vue({
    el: '#app',
    data: {
      message: 'Hello Vue!',
      employees: {},
      endDate: '',
      startDate: ''
    },
    methods: {
        addDep(){
            event.preventDefault()
            BX24.selectUser((user) => {
                console.log(user)
                this.employees = user
            });
            return false;
        },
        clearInput() {
            this.employees.name = '';
        },
    }
  })