import { Component, OnInit } from '@angular/core';
import { MenuController } from '@ionic/angular';
import * as API_CONFIG from '../../../services/api-config';
import { AppComponent } from 'src/app/app.component';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { ManagerLeavesProvider } from 'src/app/services/manager-leaves/manager-leaves';

@Component({
  selector: 'app-manager-leaves',
  templateUrl: './manager-leaves.page.html',
  styleUrls: ['./manager-leaves.page.scss'],
})
export class ManagerLeavesPage implements OnInit {
    contentType: string;
    apiUrl: API_CONFIG.API;
    baseUrl: API_CONFIG.API.BASE_URI;

    selectedSegment: any;
    leaveBalances: any;
    balancePage: any;
    fetchingBalance: any;
    approvedPage: any;
    approveList: any;
    pendingPage: any;
    pendingList: any;
    pendingTotal: any;
    fetchingPending: any;
    fetchingApprove: any;
    allPage: any;
    allList: any;
    allTotal: any;
    allHistory: any;
    fetchingAll: any;
    totalBalancePage: any;
    approveTotal: any;
    rejectPage: any;
    rejectList: any;
    rejectTotal: any;
    fetchingReject: any;
    slides: any;
    sssSegment: string;

  constructor(
    private menu: MenuController,
    public leaveProv: ManagerLeavesProvider,
    private mainApp : AppComponent,
    public comCtrl: MyCommonServices,
  ) {
    this.mainApp.setManagerMenu();
    this.mainApp.apiSessionChecker();

    this.apiUrl = API_CONFIG.API.URL;
    this.baseUrl = API_CONFIG.API.BASE_URI;
    this.contentType = API_CONFIG.CONTENT_TYPE;

    this.selectedSegment = 'balance';
    this.balancePage = "1";
    this.slides = [
      {
        id: "balance",
        title: "Leave Balance"
      },
      {
        id: "history",
        title: "Leave History"
      }
    ];


    this.sssSegment = 'all';
  }

  getLeaveBalance() {
    this.leaveProv.leaveBalanceList(this.balancePage).then(res=>{
        if (res) {
            let rslt: any = res;
            this.leaveBalances = rslt.list;
            this.balancePage = parseInt(rslt.page, 10);
            this.totalBalancePage = parseInt(rslt.total, 10);
        }
        this.fetchingBalance = false;
    }).catch(err=>{
        this.fetchingBalance = false;
        console.log(err);
    });
  }

  getApprove() {
      this.leaveProv.approvedLeaveHistory(this.approvedPage).then(res=>{
          if (res) {
              let rslt: any = res;
              this.approveList = rslt.list;
              this.approvedPage = parseInt(rslt.page, 10);;
              this.approveTotal = parseInt(rslt.total, 10);
          }
          this.fetchingApprove = false;
      }).catch(err=>{
          this.fetchingApprove = false;
          console.log(err);
      });
  }

  getPending() {
      this.leaveProv.pendingLeaveHistory(this.pendingPage).then(res=>{
          if (res) {
              let rslt: any = res;
              this.pendingList = rslt.list;
              this.pendingPage = parseInt(rslt.page, 10);;
              this.pendingTotal = parseInt(rslt.total, 10);
          }
          this.fetchingPending = false;
      }).catch(err=>{
          this.fetchingPending = false;
          console.log(err);
      });
  }

  getReject() {
      this.leaveProv.rejectLeaveHistory(this.rejectPage).then(res=>{
          if (res) {
              let rslt: any = res;
              this.rejectList = rslt.list;
              this.rejectPage = parseInt(rslt.page, 10);;
              this.rejectTotal = parseInt(rslt.total, 10);
          }
          this.fetchingReject = false;
      }).catch(err=>{
          this.fetchingReject = false;
          console.log(err);
      });
  }

  getAllHistory() {
      this.leaveProv.allLeaveHistory(this.allPage).then(res=>{
          if (res) {
              let rslt: any = res;
              this.allHistory = rslt.list;
              this.allPage = parseInt(rslt.page, 10);;
              this.allTotal = parseInt(rslt.total, 10);
          }
          this.fetchingAll = false;
      }).catch(err=>{
          this.fetchingAll = false;
          console.log(err);
      });
  }


  ngOnInit() {
    // this.menu.toggle();
    this.menu.enable(false, 'employeeMenu');
    this.menu.enable(true, 'managerMenu');
    this.getAllHistory();
    this.getReject();
    this.getPending();
    this.getLeaveBalance();

  }

}
