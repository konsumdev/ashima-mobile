import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { MenuController } from '@ionic/angular';
import { TodoProvider } from 'src/app/services/todo/todo';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import * as API_CONFIG from '../../../services/api-config';

@Component({
    selector: 'app-todo-shifts',
    templateUrl: './todo-shifts.page.html',
    styleUrls: ['./todo-shifts.page.scss'],
})

export class TodoShiftsPage implements OnInit {
    contentType: string;
    apiUrl: API_CONFIG.API;
    baseUrl: API_CONFIG.API.BASE_URI;

    // approver check
    allPage: any;
    shifts: any;
    fetching: any;

    constructor(
        private menu: MenuController,
        public todoProv: TodoProvider,
        private mainApp : AppComponent,
        public comCtrl: MyCommonServices,
    ) {
        // this.mainApp.setManagerMenu();
        this.mainApp.apiSessionChecker();

        this.apiUrl = API_CONFIG.API.URL;
        this.baseUrl = API_CONFIG.API.BASE_URI;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getList() {
        this.fetching = true;
        let spnr = this.comCtrl.presentLoading('Updating list ...');
        this.todoProv.shiftList(this.allPage+'').then(res=>{
        this.comCtrl.dismissLoading(spnr);
        let rslt: any = res;
        this.shifts = false;
        if (rslt.length) {    
            this.shifts = [];
            let currentDate : any = false;
            let currentList : any = [];

            rslt.forEach((value, index) => {

                if (value.date_filed.split(" ")[0] != currentDate) {
                
                    currentDate = value.date_filed.split(" ")[0];
                    let newGroup = {
                        date: currentDate,
                        employees: []
                    };
                    currentList = newGroup.employees;
                    this.shifts.push(newGroup);
                    
                }
                currentList.push(value);
            });
            console.log(this.shifts);
        }

        this.fetching = false;
        }).catch(err=>{
            this.comCtrl.dismissLoading(spnr);
            console.log(err);
            this.fetching = false;
        });
    }

    ngOnInit() {
        // this.menu.toggle();
        this.menu.enable(false, 'employeeMenu');
        this.menu.enable(true, 'managerMenu');
        this.getList();
    }

}
