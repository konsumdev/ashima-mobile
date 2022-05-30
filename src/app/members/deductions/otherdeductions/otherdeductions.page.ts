import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { GovloansProvider } from 'src/app/services/govloans/govloans';

@Component({
  selector: 'app-otherdeductions',
  templateUrl: './otherdeductions.page.html',
  styleUrls: ['./otherdeductions.page.scss'],
})
export class OtherdeductionsPage implements OnInit {
  fetchingAll: any;
  fakeUsers: Array<any>;
  totalPages: number;
  currentPage: any = '1';
  otherDed: any;

  constructor(
    private mainApp : AppComponent,
    private dedProv: GovloansProvider,
  ) { 
    this.mainApp.apiSessionChecker();
    this.fakeUsers = new Array(10);
    this.fetchingAll = true;
  }

  ngOnInit() {
    this.getDocuments();
  }

  getDocuments() {
    this.dedProv.getOtherDeductions(this.currentPage+'').then( res => {
        this.fetchingAll = false;
        let rslt: any = res;
        if (rslt.result) {
          this.otherDed = rslt.list;
          this.totalPages = parseInt(rslt.total, 10);
          this.currentPage = parseInt(rslt.page, 10);
        }          

    }).catch(error => {
        this.fetchingAll = false;
        console.log(error);
    });
  }

  doInfinite(infiniteScroll: any) {

    this.currentPage = this.currentPage+1;
    
    setTimeout(() => {
        this.dedProv.getOtherDeductions(this.currentPage+'').then( res => {
            let rslt: any = res;

            for(let i=0; i<rslt.list.length; i++) {
                this.otherDed.push(rslt.list[i]);
            }

        }).catch(error => {
            console.log(error);
        });

        infiniteScroll.target.complete();

        if (this.currentPage == this.totalPages) {
            infiniteScroll.target.disabled = true;
        }
    }, 1000);
  }

}
