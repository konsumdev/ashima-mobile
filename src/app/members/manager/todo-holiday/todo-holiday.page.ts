import { Component, OnInit } from '@angular/core';
import * as API_CONFIG from '../../../services/api-config';
import { MenuController } from '@ionic/angular';
import { TodoProvider } from 'src/app/services/todo/todo';
import { AppComponent } from 'src/app/app.component';
import { MyCommonServices } from 'src/app/shared/myCommonServices';

@Component({
    selector: 'app-todo-holiday',
    templateUrl: './todo-holiday.page.html',
    styleUrls: ['./todo-holiday.page.scss'],
})
export class TodoHolidayPage implements OnInit {
    contentType: string;
    apiUrl: API_CONFIG.API;
    baseUrl: API_CONFIG.API.BASE_URI;
    
    // approver check
    isApprover: any;
    timeinApp: any;
    overtimeApp: any;
    leaveApp: any;
    shiftsApp: any;
    mobileApp: any;
    timesheets: any;
    allPage: any;
    allTotal: any;
    fetching: any;

    constructor(
        private menu: MenuController,
        public todoProv: TodoProvider,
        private mainApp : AppComponent,
        public comCtrl: MyCommonServices,
    ) {
        // this.mainApp.setManagerMenu();
        this.mainApp.apiSessionChecker().then(data=>{
        
            if (data) {
                let rslt: any = data;
                this.isApprover = rslt.is_approver;
                this.overtimeApp = rslt.overtime_app;
                this.timeinApp = rslt.timein_app;
                this.leaveApp = rslt.leave_app;
                this.shiftsApp = rslt.shifts_app;
                this.mobileApp = rslt.mobile_app;
            }
        });

        this.apiUrl = API_CONFIG.API.URL;
        this.baseUrl = API_CONFIG.API.BASE_URI;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getList() {
        this.fetching = true;
        let spnr = this.comCtrl.presentLoading('Updating list ...');
        this.todoProv.holidayList(this.allPage+'').then(res=>{
            this.comCtrl.dismissLoading(spnr);
            let all_res: any = res;
            let rslt: any = all_res.list;
            this.timesheets = false;
            if (rslt.length) {   
                this.timesheets = [];             
                let currentDate : any = false;
                let currentList : any = [];

                this.allTotal = parseInt(all_res.total, 10);
                this.allPage = parseInt(all_res.page, 10);

                rslt.forEach((value, index) => {

                    if (value.change_log_date_filed.split(" ")[0] != currentDate) {
                    
                        currentDate = value.date;//value.change_log_date_filed.split(" ")[0];
                        let newGroup = {
                            date: currentDate,
                            employees: []
                        };
                        currentList = newGroup.employees;
                        this.timesheets.push(newGroup);
                        
                    }
                    currentList.push(value);
                });
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
