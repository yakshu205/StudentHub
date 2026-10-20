
#include<iostream>
#include<algorithm>
using namespace std;

void method1(int id2[],int m,int target){
    int count=0;

    for(int i=0;i<m;i++){
        count++;
        if(id2[i]==target){
            cout<<"ID Found"<<endl;
        }
    }

    cout<<"Number of Comprision Of Method 1: "<<count<<endl;
}

void method2(int id2[],int m,int target){
    int count=0;

    int low=0;
    int high=m-1;

    while(low<=high){
        int mid=low+(high-low)/2;

        count++;

        if(id2[mid]==target){
            cout<<"Id Found"<<endl;
            break;
        }
        else if(id2[mid]>target){
            high=mid-1;
        }
        else{
            low=mid+1;
        }
    }

    cout<<"Number of Comprision Of Method 2: "<<count<<endl;
}

int main(){
    int id[500];
    int n;

    cout<<"Enter Number Of id:";
    cin>>n;

    for(int i=0;i<n;i++){
        cin>>id[i];
    }

    for(int i=0;i<n;i++){
        int count=1;
        bool flag=false;

        for(int j=i-1;j>=0;j--){
            if(id[i]==id[j]){
                flag=true;
            }
        }

        if(flag)continue;
        else{
            for(int j=i+1;j<n;j++){
                if(id[i]==id[j]){
                    count++;
                }
            }
        }

        cout<<id[i]<<" : "<<count<<endl;
    }

    int id2[500];
    int m;

    cout<<"Enter Number Of id :";
    cin>>m;

    for(int i=0;i<m;i++){
        cin>>id2[i];
    }

    sort(id2,id2+m);

    int tar;

    cout<<"Enter Id Number:";
    cin>>tar;

    method1(id2,m,tar);
    method2(id2,m,tar);
}

