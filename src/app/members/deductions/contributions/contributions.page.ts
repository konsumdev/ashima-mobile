import { Component, OnInit, ViewChild } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { ContributionsProvider } from 'src/app/services/contributions/contributions';
import { IonSlides } from '@ionic/angular';

@Component({
  selector: 'app-contributions',
  templateUrl: './contributions.page.html',
  styleUrls: ['./contributions.page.scss'],
})
export class ContributionsPage implements OnInit {
  sssSegment: string;
  @ViewChild(IonSlides, {static:false}) slides: IonSlides;
  selectedSegment: string;
  allslides      : any;
  sssSummary     : any;
  sssHistory     : any;
  totalSSS       : any;
  pageSSS        : any = 1;
  phicSegment    : string;
  hdmfSegment    : string;
  phicSummary    : any;
  hdmfSummary    : any;
  phicHistory    : any;
  totalPHIC      : any;
  pagePHIC       : any = 1;
  hdmfV          : any;
  hdmfM          : any;
  totalHDMFV     : any = 1;
  pageHDMFV      : any;
  totalHDMFM     : any = 1;
  pageHDMFM      : any;

  slideOpts = {
    initialSlide: 0,
    speed: 400
  };

  constructor(
    private mainApp : AppComponent,
    private conProv: ContributionsProvider,
  ) {
    this.selectedSegment = 'sss';
    this.sssSegment = 'summary';
    this.phicSegment = 'summaryp';
    this.hdmfSegment = 'summaryh';
    this.sssSummary = "";
    this.phicSummary = "";
    this.hdmfSummary = "";
    this.hdmfM = "";
    this.hdmfV = "";
  }

  ngOnInit() {
    this.mainApp.apiSessionChecker();
    this.getSSSSummary();
    this.getEmpSSSHistory();
    this.getPHICSummary();
    this.getHDMFSummary();
    this.getEmpPHICHistory();
    this.getMandatoryHDMF();
    this.getVoluntaryHDMF();
  }

  onSegmentChanged(event: any) {
    if (this.selectedSegment == 'sss') {
        this.slides.slideTo(0);
    } else if (this.selectedSegment == 'phic') {
        this.slides.slideTo(1);
    }else if (this.selectedSegment == 'hdmf') {
        this.slides.slideTo(2);
    }   
  }

  onSlideChanged(event: any) {
    this.slides.getActiveIndex().then(index => {
        console.log(index);
        console.log('currentIndex:', index);
        if (index == 0) {
            this.selectedSegment = "sss";
        } else if (index == 1) {
          this.selectedSegment = "phic";
        } else if (index == 2) {
          this.selectedSegment = "hdmf";
        }
    });
  }

  getSSSSummary() {
      this.conProv.getSSS().then(res=>{
          let reslt: any = res;
          if (reslt.result) {
              this.sssSummary = reslt.summary;
          }
      }).catch(error=>{
          console.log(error);
      });
  }

  getEmpSSSHistory() {
      this.conProv.getSSSHistory(this.pageSSS+'').then(res=>{
          let reslt: any = res;
          if (reslt.result) {
              this.sssHistory = reslt.list;
              this.totalSSS = parseInt(reslt.total, 10);
              this.pageSSS = parseInt(reslt.page, 10);
          }
      }).catch(error=>{
          console.log(error);
      });
  }

  getPHICSummary() {
      this.conProv.getPHIC().then(res=>{
          let reslt: any = res;
          if (reslt.result) {
              this.phicHistory = reslt.list;
          }
      }).catch(error=>{
          console.log(error);
      });
  }

  getHDMFSummary() {
      this.conProv.getHDMF().then(res=>{
          let reslt: any = res;
          if (reslt.result) {
              this.hdmfSummary = reslt.summary;
          }
      }).catch(error=>{
          console.log(error);
      });
  }

  getEmpPHICHistory() {
      this.conProv.getPHICHistory(this.pageSSS+'').then(res=>{
          let reslt: any = res;
          if (reslt.result) {
              this.phicHistory = reslt.list;
              this.totalPHIC = parseInt(reslt.total, 10);
              this.pageSSS = parseInt(reslt.page, 10);
          }
      }).catch(error=>{
          console.log(error);
      });
  }

  getMandatoryHDMF() {
      this.conProv.getHDMFMHistory(this.pageSSS+'').then(res=>{
          let reslt: any = res;
          if (reslt.result) {
              this.hdmfM = reslt.list;
              this.totalHDMFM = parseInt(reslt.total, 10);
              this.pageHDMFM = parseInt(reslt.page, 10);
          }
      }).catch(error=>{
          console.log(error);
      });
  }

  getVoluntaryHDMF() {
      this.conProv.getHDMFVHistory(this.pageSSS+'').then(res=>{
          let reslt: any = res;
          if (reslt.result) {
              this.hdmfV = reslt.list;
              this.totalHDMFV = parseInt(reslt.total, 10);
              this.pageHDMFV = parseInt(reslt.page, 10);
          }
      }).catch(error=>{
          console.log(error);
      });
  }

  doInfinite(infiniteScroll: any) {
      console.log('infinite scroll');

      this.pageSSS = this.pageSSS+1;        
      setTimeout(() => {
          this.conProv.getSSSHistory(this.pageSSS+'').then( res => {
              let reslt: any = res;

              for(let i=0; i<reslt.list.length; i++) {
                  this.sssHistory.push(reslt.list[i]);
              }

          }).catch(error => {
              console.log(error);
          });

          infiniteScroll.target.complete();

          if (this.pageSSS == this.totalSSS) {
            infiniteScroll.target.disabled = true;
          }
      }, 1000);
  }

}
