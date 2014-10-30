# -*- mode: ruby -*-
# vi: set ft=ruby :
require 'json'

# get config files
# grab default settings
if File.exists? "vagrant_default.json"
  user_config = JSON.parse(IO.read("vagrant_default.json"))
end
# amend with local config if it exists
if File.exists? "vagrant_local.json"
  user_config = user_config.merge(JSON.parse(IO.read("vagrant_local.json")))
end

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # All Vagrant configuration is done here. The most common configuration
  # options are documented and commented below. For a complete reference,
  # please see the online documentation at vagrantup.com.

  # Every Vagrant virtual environment requires a box to build off of.
  config.vm.box = user_config["box"]

  # The url from where the 'config.vm.box' box will be fetched if it
  # doesn't already exist on the user's system.
  # at the moment we're fetching image from Vagrant Cloud
  # https://vagrantcloud.com/alexindigo/beaker
  # no need to specify custom url
  # config.vm.box_url = "http://files.ia.gs/beaker_0.0.1_virtualbox.box"

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.
  # config.vm.network :private_network, ip: "192.168.33.10"
  config.vm.network user_config["network"], ip: user_config["ip"]

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  # config.vm.network :public_network

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. In the example below,
  # accessing "localhost:8080" will access port 80 on the guest machine.
  # config.vm.network :forwarded_port, guest: 80, host: 8080
  if user_config.has_key?("forwarded_port")
    user_config["forwarded_port"].each do |guest_port, host_port|
      config.vm.network :forwarded_port, guest: guest_port, host: host_port
    end
  end

  # If true, then any SSH connections made will enable agent forwarding.
  # Default value: false
  # config.ssh.forward_agent = true

  # Share an additional folder to the guest VM. The first argument is
  # the path on the host to the actual folder. The second argument is
  # the path on the guest to mount the folder. And the optional third
  # argument is a set of non-required options.
  # Always have /vagrant mapped otherwise strange things happen
  if Vagrant::Util::Platform.windows?

    # allow creation of symlinks on windows
    config.vm.provider "virtualbox" do |v|
        v.customize ["setextradata", :id, "VBoxInternal2/SharedFoldersEnableSymlinksCreate/v-root", "1"]
    end

    config.vm.synced_folder ".", "/vagrant", id: "vagrant", :mount_options => ['dmode=777', 'fmode=777'], :owner => 'vagrant', :group => 'vagrant'
  else
    config.vm.synced_folder ".", "/vagrant", id: "vagrant", :nfs => true, :mount_options => ['nolock,vers=3,udp']
  end

  # map folders custom
  if user_config.has_key?("synced_folder")
    user_config["synced_folder"].each do |host_path, guest_path|

      # create absolute path
      folder_to_sync = File.expand_path(host_path)

      # last sanity check
      if File.directory? folder_to_sync
        # trying to support both windows and posix
        if Vagrant::Util::Platform.windows?
          config.vm.synced_folder folder_to_sync, guest_path, id: guest_path.gsub('/', '_'), :mount_options => ['dmode=777', 'fmode=777'], :owner => 'vagrant', :group => 'vagrant'
        else
          config.vm.synced_folder folder_to_sync, guest_path, id: guest_path.gsub('/', '_'), :nfs => true, :mount_options => ['nolock,vers=3,udp']
        end
      end
    end
  end

  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  # Example for VirtualBox:
  #
  # config.vm.provider :virtualbox do |vb|
  #   # Don't boot with headless mode
  #   vb.gui = true
  #
  #   # Use VBoxManage to customize the VM. For example to change memory:
  #   vb.customize ["modifyvm", :id, "--memory", "1024"]
  # end
  #
  # View the documentation for the provider you're using for more
  # information on available options.
  config.vm.provider :virtualbox do |vb|
    vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]
  end

  # Provisioning
  # file
  # config.vm.provision :file, source: "/home/user/.gitconfig", destination: "/home/vagrant/.gitconfig"
  # shell
  # config.vm.provision "shell", path: "vagrant/provisioners/dependencies.sh"
  # config.vm.provision "shell", path: "vagrant/provisioners/node.sh"
  # config.vm.provision "shell", path: "vagrant/provisioners/ruby.sh"
  # config.vm.provision "shell", path: "vagrant/provisioners/sass.sh"
  # config.vm.provision "shell", path: "vagrant/provisioners/grunt.sh"
  # config.vm.provision "shell", path: "vagrant/configure.sh", args: [`whoami | tr -d "\n"`]

  # custom provisions
  if user_config.has_key?("provision")
    user_config["provision"].each do |type, provision|
      # loop through each provision
      provision.each do |local_path, pro_data|
        # for `shell` type pro_data is array of arguments
        # for `file` type pro_data is destination path
        # create absolute path
        pro_file = File.expand_path(local_path)
        # last sanity check
        if File.exists? pro_file
          # for now supporting only `file` and `shell` type provisions
          if type == "file"
            config.vm.provision "file", source: pro_file, destination: pro_data
          else
            config.vm.provision "shell", path: pro_file, args: pro_data
          end
        end
      end
    end
  end

end
